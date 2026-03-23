<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Branch;
use App\Models\FinancialYear;
use App\Models\GoldPrice;
use App\Models\Invoice;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Tests\TestCase;

class InvoicePaymentLinesFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            LaravelLocalizationRedirectFilter::class,
            LocaleSessionRedirect::class,
        ]);
    }

    public function test_sales_store_supports_mixed_payment_lines_and_uses_real_bank_accounts_in_journal_and_shift_summary(): void
    {
        $branch = $this->createBranch('فرع المدفوعات');
        $user = $this->createUser($branch, 'payments-user@example.com');
        $this->createFinancialYear();
        $this->createWarehouse($branch);
        [$customerId, $supplierId] = $this->createTradingParties();
        [$itemUnitId] = $this->createInventoryFixture($branch);
        [$safeAccount, $legacyBankAccount] = $this->createBranchAccountSettings($branch, $customerId, $supplierId);

        $cardLedgerAccount = $this->createAccount('شبكة الراجحي', '8100');
        $transferLedgerAccount = $this->createAccount('تحويل الأهلي', '8101');

        $cardBankAccount = BankAccount::create([
            'branch_id' => $branch->id,
            'ledger_account_id' => $cardLedgerAccount->id,
            'account_name' => 'راجحي شبكة',
            'bank_name' => 'مصرف الراجحي',
            'terminal_name' => 'POS-01',
            'supports_credit_card' => true,
            'supports_bank_transfer' => false,
            'is_default' => true,
            'is_active' => true,
        ]);

        $transferBankAccount = BankAccount::create([
            'branch_id' => $branch->id,
            'ledger_account_id' => $transferLedgerAccount->id,
            'account_name' => 'أهلي تحويل',
            'bank_name' => 'البنك الأهلي',
            'supports_credit_card' => false,
            'supports_bank_transfer' => true,
            'is_default' => false,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin-web')
            ->post(route('admin.shifts.store', [], false), [
                'branch_id' => $branch->id,
                'opening_cash' => 50,
            ])
            ->assertRedirect(route('admin.shifts.index', [], false))
            ->assertSessionHasNoErrors();

        $shift = Shift::query()->firstOrFail();

        $response = $this->actingAs($user, 'admin-web')
            ->postJson(route('sales.store', ['type' => 'simplified'], false), [
                'type' => 'simplified',
                'bill_date' => '2026-03-22 15:30:00',
                'branch_id' => $branch->id,
                'customer_id' => $customerId,
                'bill_client_name' => 'عميل مدفوعات',
                'bill_client_phone' => '0550003333',
                'bill_client_identity_number' => '3010101010',
                'cash' => 30,
                'payment_lines' => [
                    [
                        'method_type' => 'credit_card',
                        'bank_account_id' => $cardBankAccount->id,
                        'reference_no' => 'POS-REF-1',
                        'amount' => 50,
                    ],
                    [
                        'method_type' => 'bank_transfer',
                        'bank_account_id' => $transferBankAccount->id,
                        'reference_no' => 'TRX-REF-1',
                        'amount' => 35,
                    ],
                ],
                'unit_id' => [$itemUnitId],
                'quantity' => [1],
                'weight' => [1],
                'gram_price' => [100],
                'discount' => [0],
                'no_metal' => [0],
            ]);

        $response->assertOk()->assertJson([
            'status' => true,
        ]);

        $invoice = Invoice::query()
            ->with(['paymentLines.bankAccount', 'journalEntry.documents'])
            ->where('type', 'sale')
            ->firstOrFail();

        $this->assertSame($shift->id, $invoice->shift_id);
        $this->assertSame('3010101010', $invoice->bill_client_identity_number);
        $this->assertSame(30.0, (float) $invoice->cash_paid_total);
        $this->assertSame(50.0, (float) $invoice->credit_card_paid_total);
        $this->assertSame(35.0, (float) $invoice->bank_transfer_paid_total);

        $this->assertDatabaseHas('invoice_payment_lines', [
            'invoice_id' => $invoice->id,
            'method_type' => 'cash',
            'amount' => 30,
        ]);
        $this->assertDatabaseHas('invoice_payment_lines', [
            'invoice_id' => $invoice->id,
            'method_type' => 'credit_card',
            'bank_account_id' => $cardBankAccount->id,
            'amount' => 50,
            'reference_no' => 'POS-REF-1',
        ]);
        $this->assertDatabaseHas('invoice_payment_lines', [
            'invoice_id' => $invoice->id,
            'method_type' => 'bank_transfer',
            'bank_account_id' => $transferBankAccount->id,
            'amount' => 35,
            'reference_no' => 'TRX-REF-1',
        ]);

        $journalId = $invoice->journalEntry?->id;
        $this->assertNotNull($journalId);

        $this->assertDatabaseHas('journal_entry_documents', [
            'journal_id' => $journalId,
            'account_id' => $safeAccount->id,
            'debit' => 30,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('journal_entry_documents', [
            'journal_id' => $journalId,
            'account_id' => $cardLedgerAccount->id,
            'debit' => 50,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('journal_entry_documents', [
            'journal_id' => $journalId,
            'account_id' => $transferLedgerAccount->id,
            'debit' => 35,
            'credit' => 0,
        ]);

        $shiftResponse = $this->actingAs($user, 'admin-web')
            ->get(route('admin.shifts.show', $shift, false));

        $shiftResponse->assertOk();
        $shiftResponse->assertSee('80.00');
        $shiftResponse->assertSee('85.00');

        $printResponse = $this->actingAs($user, 'admin-web')
            ->get(route('sales.show', $invoice->id, false));

        $printResponse->assertOk();
        $printResponse->assertSee('راجحي شبكة');
        $printResponse->assertSee('أهلي تحويل');
        $printResponse->assertSee('POS-REF-1');
        $printResponse->assertSee('TRX-REF-1');
        $printResponse->assertSee('3010101010');

        $this->assertSame($legacyBankAccount->id, (int) DB::table('account_settings')->where('branch_id', $branch->id)->value('bank_account'));
    }

    public function test_sales_store_rejects_non_cash_payment_line_without_valid_branch_bank_account(): void
    {
        $branch = $this->createBranch('فرع رفض الدفع');
        $user = $this->createUser($branch, 'payments-invalid-user@example.com');
        $this->createFinancialYear();
        $this->createWarehouse($branch);
        [$customerId, $supplierId] = $this->createTradingParties();
        [$itemUnitId] = $this->createInventoryFixture($branch);
        $this->createBranchAccountSettings($branch, $customerId, $supplierId);

        $foreignBranch = $this->createBranch('فرع خارجي');
        $foreignLedgerAccount = $this->createAccount('بنك خارجي', '8200');
        $foreignBankAccount = BankAccount::create([
            'branch_id' => $foreignBranch->id,
            'ledger_account_id' => $foreignLedgerAccount->id,
            'account_name' => 'حساب أجنبي',
            'bank_name' => 'بنك خارجي',
            'supports_credit_card' => true,
            'supports_bank_transfer' => true,
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin-web')
            ->post(route('admin.shifts.store', [], false), [
                'branch_id' => $branch->id,
                'opening_cash' => 0,
            ]);

        $response = $this->actingAs($user, 'admin-web')
            ->postJson(route('sales.store', ['type' => 'simplified'], false), [
                'type' => 'simplified',
                'bill_date' => '2026-03-22 12:00:00',
                'branch_id' => $branch->id,
                'customer_id' => $customerId,
                'cash' => 0,
                'payment_lines' => [
                    [
                        'method_type' => 'credit_card',
                        'bank_account_id' => $foreignBankAccount->id,
                        'amount' => 115,
                    ],
                ],
                'unit_id' => [$itemUnitId],
                'quantity' => [1],
                'weight' => [1],
                'gram_price' => [100],
                'discount' => [0],
                'no_metal' => [0],
            ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'status' => false,
        ]);
        $response->assertJsonPath('errors.0', 'الحساب البنكي المحدد غير صالح لهذا الفرع.');
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_sale_invoice_keeps_stored_unit_price_after_gold_price_changes(): void
    {
        $branch = $this->createBranch('فرع Snapshot السعر');
        $user = $this->createUser($branch, 'price-snapshot-user@example.com');
        $this->createFinancialYear();
        $this->createWarehouse($branch);
        [$customerId, $supplierId] = $this->createTradingParties();
        [$itemUnitId] = $this->createInventoryFixture($branch);
        $this->createBranchAccountSettings($branch, $customerId, $supplierId);

        GoldPrice::create([
            'currency' => 'SAR',
            'last_update' => now()->subHour(),
            'ounce_price' => 7890.10,
            'ounce_14_price' => 180.10,
            'ounce_18_price' => 231.25,
            'ounce_21_price' => 217.35,
            'ounce_22_price' => 279.40,
            'ounce_24_price' => 304.90,
            'source' => 'manual',
            'source_currency' => 'SAR',
            'meta' => ['reason' => 'initial test price'],
        ]);

        $this->actingAs($user, 'admin-web')
            ->post(route('admin.shifts.store', [], false), [
                'branch_id' => $branch->id,
                'opening_cash' => 0,
            ])
            ->assertRedirect(route('admin.shifts.index', [], false));

        $this->actingAs($user, 'admin-web')
            ->postJson(route('sales.store', ['type' => 'simplified'], false), [
                'type' => 'simplified',
                'bill_date' => '2026-03-22 16:30:00',
                'branch_id' => $branch->id,
                'customer_id' => $customerId,
                'bill_client_name' => 'عميل Snapshot السعر',
                'cash' => 249.95,
                'unit_id' => [$itemUnitId],
                'quantity' => [1],
                'weight' => [1],
                'gram_price' => [217.35],
                'discount' => [0],
                'no_metal' => [0],
            ])
            ->assertOk()
            ->assertJson([
                'status' => true,
            ]);

        $invoice = Invoice::query()->with('details')->where('type', 'sale')->firstOrFail();
        $detail = $invoice->details->firstOrFail();

        $this->assertSame(217.35, round((float) $detail->unit_price, 2));
        $this->assertSame(217.35, round((float) $detail->line_total, 2));
        $this->assertSame(249.95, round((float) $detail->round_net_total, 2));

        GoldPrice::query()->update([
            'last_update' => now(),
            'ounce_price' => 12150.40,
            'ounce_14_price' => 220.10,
            'ounce_18_price' => 280.25,
            'ounce_21_price' => 333.80,
            'ounce_22_price' => 349.15,
            'ounce_24_price' => 381.55,
            'source' => 'remote',
            'source_currency' => 'SAR',
            'meta' => ['reason' => 'price changed after invoice'],
        ]);

        $detail->refresh();
        $invoice->refresh();

        $this->assertSame(217.35, round((float) $detail->unit_price, 2));
        $this->assertSame(217.35, round((float) $detail->line_total, 2));
        $this->assertSame(249.95, round((float) $detail->round_net_total, 2));

        $printResponse = $this->actingAs($user, 'admin-web')
            ->get(route('sales.show', $invoice->id, false));

        $printResponse->assertOk();
        $printResponse->assertSee('217.35');
        $printResponse->assertSee('249.95');
    }

    public function test_purchase_store_supports_mixed_payment_lines_and_reduces_shift_expected_cash_by_cash_paid(): void
    {
        $branch = $this->createBranch('فرع مشتريات الدفع');
        $user = $this->createUser($branch, 'purchase-payments-user@example.com');
        $this->createFinancialYear();
        $this->createWarehouse($branch);
        [$customerId, $supplierId] = $this->createTradingParties();
        [$itemUnitId, $caratId] = $this->createInventoryFixture($branch);
        [$safeAccount] = $this->createBranchAccountSettings($branch, $customerId, $supplierId);

        $cardLedgerAccount = $this->createAccount('شبكة شراء', '8300');
        $cardBankAccount = BankAccount::create([
            'branch_id' => $branch->id,
            'ledger_account_id' => $cardLedgerAccount->id,
            'account_name' => 'شبكة المشتريات',
            'bank_name' => 'بنك المشتريات',
            'terminal_name' => 'PUR-POS-1',
            'supports_credit_card' => true,
            'supports_bank_transfer' => false,
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin-web')
            ->post(route('admin.shifts.store', [], false), [
                'branch_id' => $branch->id,
                'opening_cash' => 50,
            ])
            ->assertRedirect(route('admin.shifts.index', [], false))
            ->assertSessionHasNoErrors();

        $shift = Shift::query()->firstOrFail();

        $response = $this->actingAs($user, 'admin-web')
            ->postJson(route('purchases.store', [], false), [
                'bill_date' => '2026-03-22 16:45:00',
                'branch_id' => $branch->id,
                'carat_type' => 'crafted',
                'purchase_type' => 'normal',
                'supplier_id' => $supplierId,
                'bill_client_name' => 'مورد مدفوع',
                'bill_client_phone' => '0558888888',
                'bill_client_identity_number' => '4010101010',
                'cash' => 40,
                'payment_lines' => [
                    [
                        'method_type' => 'credit_card',
                        'bank_account_id' => $cardBankAccount->id,
                        'reference_no' => 'PUR-REF-1',
                        'amount' => 75,
                    ],
                ],
                'unit_id' => [$itemUnitId],
                'carats_id' => [$caratId],
                'weight' => [1],
                'item_total_cost' => [100],
                'item_total_labor_cost' => [0],
                'discount' => [0],
            ]);

        $response->assertOk()->assertJson([
            'status' => true,
        ]);

        $invoice = Invoice::query()
            ->with(['paymentLines.bankAccount', 'journalEntry.documents'])
            ->where('type', 'purchase')
            ->firstOrFail();

        $this->assertSame($shift->id, $invoice->shift_id);
        $this->assertSame('4010101010', $invoice->bill_client_identity_number);
        $this->assertSame(40.0, (float) $invoice->cash_paid_total);
        $this->assertSame(75.0, (float) $invoice->credit_card_paid_total);

        $this->assertDatabaseHas('invoice_payment_lines', [
            'invoice_id' => $invoice->id,
            'method_type' => 'cash',
            'amount' => 40,
        ]);
        $this->assertDatabaseHas('invoice_payment_lines', [
            'invoice_id' => $invoice->id,
            'method_type' => 'credit_card',
            'bank_account_id' => $cardBankAccount->id,
            'amount' => 75,
            'reference_no' => 'PUR-REF-1',
        ]);

        $journalId = $invoice->journalEntry?->id;
        $this->assertNotNull($journalId);

        $this->assertDatabaseHas('journal_entry_documents', [
            'journal_id' => $journalId,
            'account_id' => $safeAccount->id,
            'debit' => 0,
            'credit' => 40,
        ]);
        $this->assertDatabaseHas('journal_entry_documents', [
            'journal_id' => $journalId,
            'account_id' => $cardLedgerAccount->id,
            'debit' => 0,
            'credit' => 75,
        ]);

        $shiftResponse = $this->actingAs($user, 'admin-web')
            ->get(route('admin.shifts.show', $shift, false));

        $shiftResponse->assertOk();
        $shiftResponse->assertSee('مشتريات نقدية');
        $shiftResponse->assertSee('40.00');
        $shiftResponse->assertSee('10.00');

        $printResponse = $this->actingAs($user, 'admin-web')
            ->get(route('purchases.show', $invoice->id, false));

        $printResponse->assertOk();
        $printResponse->assertSee('بنك المشتريات');
        $printResponse->assertSee('PUR-REF-1');
        $printResponse->assertSee('4010101010');
    }

    public function test_sales_return_store_supports_refund_payment_lines_and_reduces_shift_expected_cash_by_cash_refund(): void
    {
        $branch = $this->createBranch('فرع مرتجع البيع');
        $user = $this->createUser($branch, 'sale-return-payments-user@example.com');
        $this->createFinancialYear();
        $this->createWarehouse($branch);
        [$customerId, $supplierId] = $this->createTradingParties();
        [$itemUnitId] = $this->createInventoryFixture($branch);
        [$safeAccount] = $this->createBranchAccountSettings($branch, $customerId, $supplierId);

        $cardLedgerAccount = $this->createAccount('شبكة المرتجع', '8400');
        $cardBankAccount = BankAccount::create([
            'branch_id' => $branch->id,
            'ledger_account_id' => $cardLedgerAccount->id,
            'account_name' => 'جهاز مرتجع',
            'bank_name' => 'بنك المرتجع',
            'terminal_name' => 'RET-POS-1',
            'supports_credit_card' => true,
            'supports_bank_transfer' => false,
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin-web')
            ->post(route('admin.shifts.store', [], false), [
                'branch_id' => $branch->id,
                'opening_cash' => 20,
            ])
            ->assertRedirect(route('admin.shifts.index', [], false))
            ->assertSessionHasNoErrors();

        $shift = Shift::query()->firstOrFail();

        $saleResponse = $this->actingAs($user, 'admin-web')
            ->postJson(route('sales.store', ['type' => 'simplified'], false), [
                'type' => 'simplified',
                'bill_date' => '2026-03-22 17:00:00',
                'branch_id' => $branch->id,
                'customer_id' => $customerId,
                'bill_client_name' => 'عميل مرتجع',
                'bill_client_phone' => '0551231234',
                'bill_client_identity_number' => '5010101010',
                'cash' => 115,
                'payment_lines' => [],
                'unit_id' => [$itemUnitId],
                'quantity' => [1],
                'weight' => [1],
                'gram_price' => [100],
                'discount' => [0],
                'no_metal' => [0],
            ]);

        $saleResponse->assertOk()->assertJson([
            'status' => true,
        ]);

        $saleInvoice = Invoice::query()
            ->with('details')
            ->where('type', 'sale')
            ->firstOrFail();

        $saleDetailId = $saleInvoice->details->firstOrFail()->id;

        $returnResponse = $this->actingAs($user, 'admin-web')
            ->post(route('sales_return.store', ['type' => 'simplified', 'id' => $saleInvoice->id], false), [
                'checkDetail' => [$saleDetailId],
                'cash' => 25,
                'payment_lines' => [
                    [
                        'method_type' => 'credit_card',
                        'bank_account_id' => $cardBankAccount->id,
                        'reference_no' => 'RET-REF-1',
                        'amount' => 90,
                    ],
                ],
            ]);

        $returnResponse
            ->assertRedirect(route('sales_return.index', ['type' => 'simplified'], false))
            ->assertSessionHasNoErrors();

        $returnInvoice = Invoice::query()
            ->with(['paymentLines.bankAccount', 'journalEntry.documents', 'details'])
            ->where('type', 'sale_return')
            ->firstOrFail();

        $this->assertSame($saleInvoice->id, (int) $returnInvoice->parent_id);
        $this->assertSame($shift->id, $returnInvoice->shift_id);
        $this->assertSame('5010101010', $returnInvoice->bill_client_identity_number);
        $this->assertNotNull($returnInvoice->journalEntry?->id);
        $this->assertNotSame($saleInvoice->journalEntry?->id, $returnInvoice->journalEntry?->id);
        $this->assertSame(25.0, (float) $returnInvoice->cash_paid_total);
        $this->assertSame(90.0, (float) $returnInvoice->credit_card_paid_total);

        $this->assertDatabaseHas('invoice_details', [
            'invoice_id' => $returnInvoice->id,
            'parent_id' => $saleDetailId,
        ]);

        $this->assertDatabaseHas('invoice_payment_lines', [
            'invoice_id' => $returnInvoice->id,
            'method_type' => 'cash',
            'amount' => 25,
        ]);
        $this->assertDatabaseHas('invoice_payment_lines', [
            'invoice_id' => $returnInvoice->id,
            'method_type' => 'credit_card',
            'bank_account_id' => $cardBankAccount->id,
            'amount' => 90,
            'reference_no' => 'RET-REF-1',
        ]);

        $journalId = $returnInvoice->journalEntry?->id;
        $this->assertNotNull($journalId);

        $this->assertDatabaseHas('journal_entry_documents', [
            'journal_id' => $journalId,
            'account_id' => $safeAccount->id,
            'debit' => 0,
            'credit' => 25,
        ]);
        $this->assertDatabaseHas('journal_entry_documents', [
            'journal_id' => $journalId,
            'account_id' => $cardLedgerAccount->id,
            'debit' => 0,
            'credit' => 90,
        ]);

        $shiftResponse = $this->actingAs($user, 'admin-web')
            ->get(route('admin.shifts.show', $shift, false));

        $shiftResponse->assertOk();
        $shiftResponse->assertSee('مرتجع بيع نقدي');
        $shiftResponse->assertSee('25.00');
        $shiftResponse->assertSee('110.00');

        $printResponse = $this->actingAs($user, 'admin-web')
            ->get(route('sales_return.show', $returnInvoice->id, false));

        $printResponse->assertOk();
        $printResponse->assertSee('بنك المرتجع');
        $printResponse->assertSee('RET-REF-1');
        $printResponse->assertSee('5010101010');
    }

    public function test_purchase_return_store_supports_receipt_payment_lines_and_increases_shift_expected_cash_by_cash_refund(): void
    {
        $branch = $this->createBranch('فرع مردود الشراء');
        $user = $this->createUser($branch, 'purchase-return-payments-user@example.com');
        $this->createFinancialYear();
        $this->createWarehouse($branch);
        [$customerId, $supplierId] = $this->createTradingParties();
        [$itemUnitId, $caratId] = $this->createInventoryFixture($branch);
        [$safeAccount] = $this->createBranchAccountSettings($branch, $customerId, $supplierId);

        $cardLedgerAccount = $this->createAccount('شبكة مردود الشراء', '8450');
        $cardBankAccount = BankAccount::create([
            'branch_id' => $branch->id,
            'ledger_account_id' => $cardLedgerAccount->id,
            'account_name' => 'جهاز مردود الشراء',
            'bank_name' => 'بنك مردود الشراء',
            'terminal_name' => 'PUR-RET-POS-1',
            'supports_credit_card' => true,
            'supports_bank_transfer' => false,
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin-web')
            ->post(route('admin.shifts.store', [], false), [
                'branch_id' => $branch->id,
                'opening_cash' => 20,
            ])
            ->assertRedirect(route('admin.shifts.index', [], false))
            ->assertSessionHasNoErrors();

        $shift = Shift::query()->firstOrFail();

        $purchaseResponse = $this->actingAs($user, 'admin-web')
            ->postJson(route('purchases.store', [], false), [
                'bill_date' => '2026-03-22 18:15:00',
                'branch_id' => $branch->id,
                'carat_type' => 'crafted',
                'purchase_type' => 'normal',
                'supplier_id' => $supplierId,
                'bill_client_name' => 'مورد مردود',
                'bill_client_phone' => '0559999999',
                'bill_client_identity_number' => '6010101010',
                'cash' => 115,
                'payment_lines' => [],
                'unit_id' => [$itemUnitId],
                'carats_id' => [$caratId],
                'weight' => [1],
                'item_total_cost' => [100],
                'item_total_labor_cost' => [0],
                'discount' => [0],
            ]);

        $purchaseResponse->assertOk()->assertJson([
            'status' => true,
        ]);

        $purchaseInvoice = Invoice::query()
            ->with('details')
            ->where('type', 'purchase')
            ->firstOrFail();

        $purchaseDetailId = $purchaseInvoice->details->firstOrFail()->id;

        $returnResponse = $this->actingAs($user, 'admin-web')
            ->post(route('purchase_return.store', ['id' => $purchaseInvoice->id], false), [
                'checkDetail' => [$purchaseDetailId],
                'cash' => 15,
                'payment_lines' => [
                    [
                        'method_type' => 'credit_card',
                        'bank_account_id' => $cardBankAccount->id,
                        'reference_no' => 'PUR-RET-REF-1',
                        'amount' => 100,
                    ],
                ],
            ]);

        $returnResponse
            ->assertRedirect(route('purchase_return.index', [], false))
            ->assertSessionHasNoErrors();

        $returnInvoice = Invoice::query()
            ->with(['paymentLines.bankAccount', 'journalEntry.documents', 'details'])
            ->where('type', 'purchase_return')
            ->firstOrFail();

        $this->assertSame($purchaseInvoice->id, (int) $returnInvoice->parent_id);
        $this->assertSame($shift->id, $returnInvoice->shift_id);
        $this->assertSame('6010101010', $returnInvoice->bill_client_identity_number);
        $this->assertSame(15.0, (float) $returnInvoice->cash_paid_total);
        $this->assertSame(100.0, (float) $returnInvoice->credit_card_paid_total);

        $this->assertDatabaseHas('invoice_details', [
            'invoice_id' => $returnInvoice->id,
            'parent_id' => $purchaseDetailId,
        ]);

        $this->assertDatabaseHas('invoice_payment_lines', [
            'invoice_id' => $returnInvoice->id,
            'method_type' => 'cash',
            'amount' => 15,
        ]);
        $this->assertDatabaseHas('invoice_payment_lines', [
            'invoice_id' => $returnInvoice->id,
            'method_type' => 'credit_card',
            'bank_account_id' => $cardBankAccount->id,
            'amount' => 100,
            'reference_no' => 'PUR-RET-REF-1',
        ]);

        $journalId = $returnInvoice->journalEntry?->id;
        $this->assertNotNull($journalId);

        $this->assertDatabaseHas('journal_entry_documents', [
            'journal_id' => $journalId,
            'account_id' => $safeAccount->id,
            'debit' => 15,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('journal_entry_documents', [
            'journal_id' => $journalId,
            'account_id' => $cardLedgerAccount->id,
            'debit' => 100,
            'credit' => 0,
        ]);

        $shiftResponse = $this->actingAs($user, 'admin-web')
            ->get(route('admin.shifts.show', $shift, false));

        $shiftResponse->assertOk();
        $shiftResponse->assertSee('مردود شراء نقدي');
        $shiftResponse->assertSee('15.00');
        $shiftResponse->assertSee('-80.00');

        $printResponse = $this->actingAs($user, 'admin-web')
            ->get(route('purchase_return.show', $returnInvoice->id, false));

        $printResponse->assertOk();
        $printResponse->assertSee('بنك مردود الشراء');
        $printResponse->assertSee('PUR-RET-REF-1');
        $printResponse->assertSee('6010101010');
    }

    private function createBranch(string $name): Branch
    {
        return Branch::create([
            'name' => ['ar' => $name, 'en' => $name],
            'phone' => '123456789',
            'status' => true,
        ]);
    }

    private function createUser(Branch $branch, string $email): User
    {
        return User::create([
            'name' => 'Payment User',
            'email' => $email,
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);
    }

    private function createFinancialYear(): FinancialYear
    {
        return FinancialYear::create([
            'description' => 'السنة المالية 2026',
            'from' => '2026-01-01',
            'to' => '2026-12-31',
            'is_closed' => false,
            'is_active' => true,
        ]);
    }

    private function createTradingParties(): array
    {
        $customerAccount = $this->createAccount('ذمم العملاء', '8001');
        $supplierAccount = $this->createAccount('ذمم الموردين', '8002');

        $customerId = DB::table('customers')->insertGetId([
            'name' => 'عميل المدفوعات',
            'phone' => '0551111111',
            'account_id' => $customerAccount->id,
            'type' => 'customer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $supplierId = DB::table('customers')->insertGetId([
            'name' => 'مورد المدفوعات',
            'phone' => '0552222222',
            'account_id' => $supplierAccount->id,
            'type' => 'supplier',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$customerId, $supplierId];
    }

    private function createWarehouse(Branch $branch): int
    {
        return DB::table('warehouses')->insertGetId([
            'name' => 'مخزن المدفوعات',
            'code' => 'WH-PAY-1',
            'branch_id' => $branch->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createInventoryFixture(Branch $branch): array
    {
        $taxId = DB::table('taxes')->insertGetId([
            'title' => 'VAT',
            'rate' => 15,
            'zatca_code' => 'S',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $caratTypeId = DB::table('gold_carat_types')->insertGetId([
            'title' => 'مشغول',
            'key' => 'crafted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $caratId = DB::table('gold_carats')->insertGetId([
            'title' => json_encode(['ar' => 'عيار 21', 'en' => '21K'], JSON_UNESCAPED_UNICODE),
            'label' => 'C21',
            'tax_id' => $taxId,
            'transform_factor' => '1',
            'is_pure' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $itemId = DB::table('items')->insertGetId([
            'title' => json_encode(['ar' => 'قطعة ذهب', 'en' => 'Gold Piece'], JSON_UNESCAPED_UNICODE),
            'code' => 'PAY-0001',
            'description' => null,
            'category_id' => null,
            'branch_id' => $branch->id,
            'gold_carat_id' => $caratId,
            'gold_carat_type_id' => $caratTypeId,
            'no_metal' => 0,
            'no_metal_type' => 'fixed',
            'labor_cost_per_gram' => 10,
            'profit_margin_per_gram' => 0,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $itemUnitId = DB::table('item_units')->insertGetId([
            'item_id' => $itemId,
            'initial_cost_per_gram' => 80,
            'average_cost_per_gram' => 80,
            'current_cost_per_gram' => 80,
            'barcode' => 'PAY-0001-UNIT',
            'weight' => 1,
            'is_default' => true,
            'is_sold' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$itemUnitId, $caratId];
    }

    private function createBranchAccountSettings(Branch $branch, int $customerId, int $supplierId): array
    {
        $safeAccount = $this->createAccount('الصندوق التشغيلي', '8050');
        $bankAccount = $this->createAccount('البنك الافتراضي', '8051');
        $salesAccount = $this->createAccount('المبيعات', '8052');
        $returnSalesAccount = $this->createAccount('مرتجعات المبيعات', '8053');
        $stockCraftedAccount = $this->createAccount('مخزون المشغول', '8054');
        $madeAccount = $this->createAccount('أجرة الصياغة', '8055');
        $costCraftedAccount = $this->createAccount('تكلفة المشغول', '8056');
        $salesTaxAccount = $this->createAccount('ضريبة المبيعات', '8057');
        $purchaseTaxAccount = $this->createAccount('ضريبة المشتريات', '8058');

        $customerAccountId = DB::table('customers')->where('id', $customerId)->value('account_id');
        $supplierAccountId = DB::table('customers')->where('id', $supplierId)->value('account_id');

        DB::table('account_settings')->insert([
            'safe_account' => $safeAccount->id,
            'bank_account' => $bankAccount->id,
            'sales_account' => $salesAccount->id,
            'return_sales_account' => $returnSalesAccount->id,
            'stock_account_crafted' => $stockCraftedAccount->id,
            'stock_account_scrap' => $stockCraftedAccount->id,
            'stock_account_pure' => $stockCraftedAccount->id,
            'made_account' => $madeAccount->id,
            'cost_account_crafted' => $costCraftedAccount->id,
            'cost_account_scrap' => $costCraftedAccount->id,
            'cost_account_pure' => $costCraftedAccount->id,
            'reverse_profit_account' => $salesAccount->id,
            'profit_account' => $salesAccount->id,
            'sales_tax_account' => $salesTaxAccount->id,
            'purchase_tax_account' => $purchaseTaxAccount->id,
            'sales_tax_excise_account' => $salesTaxAccount->id,
            'supplier_default_account' => $supplierAccountId,
            'clients_account' => $customerAccountId,
            'suppliers_account' => $supplierAccountId,
            'branch_id' => $branch->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$safeAccount, $bankAccount];
    }

    private function createAccount(string $name, string $code): Account
    {
        return Account::create([
            'name' => $name,
            'code' => $code,
        ]);
    }
}
