<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\BranchInvoiceTermsSetting;
use App\Services\Invoices\InvoiceTermsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InvoiceTermsFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withoutMiddleware([
            LaravelLocalizationRedirectFilter::class,
            LocaleSessionRedirect::class,
        ]);
    }

    public function test_sales_create_page_hides_default_invoice_terms_ui_while_service_keeps_context_defaults(): void
    {
        SystemSetting::putValue('invoice_terms_templates', json_encode([
            [
                'key' => 'retail-exchange',
                'context' => InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED,
                'title' => 'استبدال وبيع تجزئة',
                'content' => "الاستبدال خلال 3 أيام\nمع الفاتورة الأصلية",
            ],
            [
                'key' => 'company-sales',
                'context' => InvoiceTermsService::CONTEXT_SALES_STANDARD,
                'title' => 'بيع شركات',
                'content' => "تعتمد الفاتورة على بيانات العميل الضريبية\nولا يتم التعديل إلا بالمراجعة",
            ],
            [
                'key' => 'purchase-supplier',
                'context' => InvoiceTermsService::CONTEXT_PURCHASES,
                'title' => 'شراء مورد',
                'content' => "يعتمد الوزن بعد الفحص",
            ],
        ], JSON_UNESCAPED_UNICODE));
        SystemSetting::putValue('default_invoice_terms_template_keys', json_encode([
            InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED => 'retail-exchange',
            InvoiceTermsService::CONTEXT_SALES_STANDARD => 'company-sales',
            InvoiceTermsService::CONTEXT_PURCHASES => 'purchase-supplier',
        ], JSON_UNESCAPED_UNICODE));
        $admin = $this->createAdminUser([
            'employee.simplified_tax_invoices.add',
            'employee.tax_invoices.add',
        ]);

        $simplifiedResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('sales.create', ['type' => 'simplified'], false));

        $simplifiedResponse->assertOk();
        $simplifiedResponse->assertDontSee('الشروط الافتراضية');
        $simplifiedResponse->assertDontSee('الاستبدال خلال 3 أيام');
        $simplifiedResponse->assertDontSee('قالب الشروط');
        $simplifiedResponse->assertDontSee('name="invoice_terms"', false);

        $standardResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('sales.create', ['type' => 'standard'], false));

        $standardResponse->assertOk();
        $standardResponse->assertDontSee('الشروط الافتراضية');
        $standardResponse->assertDontSee('تعتمد الفاتورة على بيانات العميل الضريبية');
        $standardResponse->assertDontSee('الاستبدال خلال 3 أيام');

        $service = app(InvoiceTermsService::class);
        $this->assertSame(
            "الاستبدال خلال 3 أيام\nمع الفاتورة الأصلية",
            $service->defaultTerms(InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED),
        );
        $this->assertSame(
            "تعتمد الفاتورة على بيانات العميل الضريبية\nولا يتم التعديل إلا بالمراجعة",
            $service->defaultTerms(InvoiceTermsService::CONTEXT_SALES_STANDARD),
        );
    }

    public function test_purchases_create_page_hides_default_invoice_terms_ui_while_service_keeps_context_default(): void
    {
        SystemSetting::putValue('invoice_terms_templates', json_encode([
            [
                'key' => 'supplier-standard',
                'context' => InvoiceTermsService::CONTEXT_PURCHASES,
                'title' => 'مورد قياسي',
                'content' => "الشراء النهائي بعد الفحص\nولا يقبل الإلغاء",
            ],
        ], JSON_UNESCAPED_UNICODE));
        SystemSetting::putValue('default_invoice_terms_template_keys', json_encode([
            InvoiceTermsService::CONTEXT_PURCHASES => 'supplier-standard',
        ], JSON_UNESCAPED_UNICODE));
        $admin = $this->createAdminUser([
            'employee.purchase_invoices.add',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('purchases.create', [], false));

        $response->assertOk();
        $response->assertDontSee('الشروط الافتراضية');
        $response->assertDontSee('الشراء النهائي بعد الفحص');
        $response->assertDontSee('قالب الشروط');
        $response->assertDontSee('name="invoice_terms"', false);

        $service = app(InvoiceTermsService::class);
        $this->assertSame(
            "الشراء النهائي بعد الفحص\nولا يقبل الإلغاء",
            $service->defaultTerms(InvoiceTermsService::CONTEXT_PURCHASES),
        );
    }

    public function test_legacy_invoice_terms_configuration_still_resolves_defaults_for_all_contexts(): void
    {
        SystemSetting::putValue('default_invoice_terms', "شروط قديمة ما زالت فعالة\nحتى يعاد تعريفها");
        SystemSetting::putValue('invoice_terms_templates', json_encode([
            [
                'key' => 'legacy-retail',
                'title' => 'بيع قديم',
                'content' => "بيع قديم\nنص احتياطي",
            ],
        ], JSON_UNESCAPED_UNICODE));
        SystemSetting::putValue('default_invoice_terms_template_key', 'legacy-retail');
        $service = app(InvoiceTermsService::class);

        $this->assertSame(
            "شروط قديمة ما زالت فعالة\nحتى يعاد تعريفها",
            $service->defaultTerms(InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED),
        );
        $this->assertSame(
            "شروط قديمة ما زالت فعالة\nحتى يعاد تعريفها",
            $service->defaultTerms(InvoiceTermsService::CONTEXT_SALES_STANDARD),
        );
        $this->assertSame(
            "شروط قديمة ما زالت فعالة\nحتى يعاد تعريفها",
            $service->defaultTerms(InvoiceTermsService::CONTEXT_PURCHASES),
        );
    }

    public function test_invoice_terms_settings_page_exposes_modal_based_management_interface(): void
    {
        $admin = $this->createAdminUser([
            'employee.system_settings.show',
            'employee.system_settings.edit',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('admin.system-settings.invoice-terms.edit', [], false));

        $response->assertOk();
        $response->assertSee('إضافة شروط جديدة');
        $response->assertSee('invoice-terms-template-modal', false);
        $response->assertSee('الصفحة التابعة لها');
        $response->assertSee('فواتير البيع المبسطة');
        $response->assertSee('فواتير مبيعات الشركات');
        $response->assertSee('فواتير المشتريات');
        $response->assertSee('إظهار هذه الشروط عند طباعة الفاتورة');
        $response->assertSee('وأي تعديل عليها يظهر على فواتير هذا الفرع عند الطباعة فور حفظه');
    }

    public function test_sales_print_page_can_hide_invoice_terms_without_deleting_saved_template(): void
    {
        $branch = $this->createBranch('فرع إخفاء الشروط', 'sales-hide-terms@example.com', '111222333');
        $user = $this->createUser($branch, 'sales-hide-terms-user@example.com');
        $invoice = $this->createInvoice($branch, $user, 'sale', [
            'sale_type' => 'simplified',
            'invoice_terms' => "هذه الشروط محفوظة\nلكنها يجب ألا تطبع",
        ]);

        SystemSetting::putValue('invoice_terms_templates', json_encode([
            [
                'key' => 'retail-hidden',
                'context' => InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED,
                'title' => 'شروط مخفية',
                'content' => "هذه الشروط محفوظة\nلكنها يجب ألا تطبع",
                'show_on_invoice' => false,
            ],
        ], JSON_UNESCAPED_UNICODE));
        SystemSetting::putValue('default_invoice_terms_template_keys', json_encode([
            InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED => 'retail-hidden',
        ], JSON_UNESCAPED_UNICODE));

        $response = $this
            ->actingAs($user, 'admin-web')
            ->get(route('sales.show', ['id' => $invoice->id], false));

        $response->assertOk();
        $response->assertDontSee('شروط الفاتورة');
        $response->assertDontSee('هذه الشروط محفوظة');
        $response->assertDontSee('لكنها يجب ألا تطبع');
    }

    /**
     * الإخفاء يتبع القالب الافتراضي لصفحة الفاتورة في فرعها: قالب مطفأ عن
     * الطباعة لا يطبع شروطًا ولو بقيت نسخة قديمة محفوظة داخل الفاتورة.
     */
    public function test_sales_print_page_hides_terms_when_the_branch_default_template_is_hidden(): void
    {
        $branch = $this->createBranch('فرع مطابقة الشروط', 'sales-terms-match@example.com', '777888999');
        $user = $this->createUser($branch, 'sales-terms-match-user@example.com');
        $invoice = $this->createInvoice($branch, $user, 'sale', [
            'sale_type' => 'simplified',
            'invoice_terms' => "شروط محفوظة قديمة\nيجب إخفاؤها عند الطباعة",
        ]);

        BranchInvoiceTermsSetting::query()->create([
            'branch_id' => $branch->id,
            'templates' => [[
                'key' => 'retail-hidden-branch',
                'context' => InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED,
                'title' => 'مخفي',
                'content' => "شروط الفرع الحالية\nمخفية عن الطباعة",
                'show_on_invoice' => false,
            ]],
            'default_template_keys' => [
                InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED => 'retail-hidden-branch',
            ],
        ]);

        $response = $this
            ->actingAs($user, 'admin-web')
            ->get(route('sales.show', ['id' => $invoice->id], false));

        $response->assertOk();
        $response->assertDontSee('شروط الفاتورة');
        $response->assertDontSee('شروط الفرع الحالية');
        $response->assertDontSee('شروط محفوظة قديمة');
    }

    /**
     * جوهر ما يطلبه المالك: يعدّل شروط الفرع فيظهر التعديل على فواتير هذا
     * الفرع عند الطباعة، ولا تبقى النسخة القديمة المحفوظة داخل الفاتورة.
     */
    public function test_sales_print_page_shows_the_current_branch_terms_after_they_are_edited(): void
    {
        $branch = $this->createBranch('فرع المبيعات', 'sales-branch@example.com', '111111111');
        $user = $this->createUser($branch, 'sales-user@example.com');
        $invoice = $this->createInvoice($branch, $user, 'sale', [
            'sale_type' => 'simplified',
            'bill_client_name' => 'عميل نقدي',
            'bill_client_phone' => '0555555555',
            'invoice_terms' => "الشرط القديم وقت البيع\nلم يعد ساريًا",
        ]);

        BranchInvoiceTermsSetting::query()->create([
            'branch_id' => $branch->id,
            'templates' => [[
                'key' => 'retail-edited',
                'context' => InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED,
                'title' => 'شروط معدلة',
                'content' => "الشرط الجديد بعد التعديل\nيجب أن يظهر على الفاتورة",
                'show_on_invoice' => true,
            ]],
            'default_template_keys' => [
                InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED => 'retail-edited',
            ],
        ]);

        $response = $this
            ->actingAs($user, 'admin-web')
            ->get(route('sales.show', ['id' => $invoice->id], false));

        $response->assertOk();
        $response->assertSee('شروط الفاتورة');
        $response->assertSee('الشرط الجديد بعد التعديل');
        $response->assertSee('يجب أن يظهر على الفاتورة');
        $response->assertDontSee('الشرط القديم وقت البيع');

        // بيانات الفاتورة نفسها لا تُمَس: النسخة المحفوظة تبقى سجلًّا لما صدر
        $this->assertSame("الشرط القديم وقت البيع\nلم يعد ساريًا", $invoice->refresh()->invoice_terms);
    }

    /**
     * انحدار العطل المبلَّغ عنه: المالك يعدّل الشروط من صفحة الإعدادات، وكانت
     * فواتير الفرع تبقى على النصّ القديم لأن الشروط كانت مخزَّنة لكل مستخدم
     * على حدة، فلا يصلها تعديله. الآن تظهر على فواتير الفرع مهما كان مُصدرها.
     */
    public function test_owner_edit_shows_on_invoices_issued_by_another_user_in_the_same_branch(): void
    {
        $admin = $this->createAdminUser([
            'employee.system_settings.show',
            'employee.system_settings.edit',
        ], 'owner-edit-invoice-terms@example.com');
        $cashier = $this->createUser($admin->branch, 'cashier-invoice-terms@example.com');
        $invoice = $this->createInvoice($admin->branch, $cashier, 'sale', [
            'sale_type' => 'simplified',
            'invoice_terms' => "الشرط القديم المحفوظ\nوقت إصدار الفاتورة",
        ]);

        $this
            ->actingAs($admin, 'admin-web')
            ->patch(route('admin.system-settings.invoice-terms.update', [], false), [
                'templates' => [
                    [
                        'key' => 'retail-owner-edit',
                        'context' => InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED,
                        'title' => 'شروط بعد تعديل المالك',
                        'content' => "شرط عدّله المالك للتو\nويجب أن يطبع على فواتير الفرع",
                        'show_on_invoice' => '1',
                    ],
                ],
                'default_template_keys' => [
                    InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED => 'retail-owner-edit',
                ],
            ])
            ->assertRedirect(route('admin.system-settings.invoice-terms.edit', [], false));

        $response = $this
            ->actingAs($cashier, 'admin-web')
            ->get(route('sales.show', ['id' => $invoice->id], false));

        $response->assertOk();
        $response->assertSee('شرط عدّله المالك للتو');
        $response->assertSee('ويجب أن يطبع على فواتير الفرع');
        $response->assertDontSee('الشرط القديم المحفوظ');
    }

    /**
     * فاتورة صدرت قبل ضبط الشروط فلم تُحفظ فيها نسخة: تبقى بلا شروط ولا سبب
     * ظاهر. صارت ترث قالب صفحتها الحالي، فالتعديل يظهر عليها.
     */
    public function test_invoice_without_saved_terms_inherits_the_current_template(): void
    {
        $branch = $this->createBranch('فرع الوراثة', 'inherit-branch@example.com', '111111112');
        $user = $this->createUser($branch, 'inherit-user@example.com');
        $invoice = $this->createInvoice($branch, $user, 'sale', [
            'sale_type' => 'simplified',
            'bill_client_name' => 'عميل نقدي',
            'invoice_terms' => null,
        ]);

        SystemSetting::putValue('default_invoice_terms', 'شرط محدَّث يظهر على الفاتورة القديمة');

        $response = $this
            ->actingAs($user, 'admin-web')
            ->get(route('sales.show', ['id' => $invoice->id], false));

        $response->assertOk();
        $response->assertSee('شروط الفاتورة');
        $response->assertSee('شرط محدَّث يظهر على الفاتورة القديمة');
    }

    /**
     * والوراثة تتبع القالب في إخفائه أيضًا: قالب مطفأ في الطباعة لا يُظهر
     * شروطًا على فاتورة ورثت منه.
     */
    public function test_invoice_without_saved_terms_stays_empty_when_the_template_is_hidden(): void
    {
        $branch = $this->createBranch('فرع الإخفاء', 'hidden-branch@example.com', '111111113');
        $user = $this->createUser($branch, 'hidden-user@example.com');
        $invoice = $this->createInvoice($branch, $user, 'sale', [
            'sale_type' => 'simplified',
            'bill_client_name' => 'عميل نقدي',
            'invoice_terms' => null,
        ]);

        app(InvoiceTermsService::class)->setTemplates([
            [
                'key' => 'retail-exchange',
                'title' => 'استبدال وبيع تجزئة',
                'content' => 'شرط مخفي عن الطباعة',
                'context' => InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED,
                'show_on_invoice' => false,
            ],
        ], [InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED => 'retail-exchange']);

        $response = $this
            ->actingAs($user, 'admin-web')
            ->get(route('sales.show', ['id' => $invoice->id], false));

        $response->assertOk();
        $response->assertDontSee('شرط مخفي عن الطباعة');
    }

    public function test_invoice_terms_settings_update_persists_full_multiline_template_content(): void
    {
        $admin = $this->createAdminUser([
            'employee.system_settings.show',
            'employee.system_settings.edit',
        ]);

        $response = $this
            ->actingAs($admin, 'admin-web')
            ->patch(route('admin.system-settings.invoice-terms.update', [], false), [
                'templates' => [
                    [
                        'key' => 'retail-custom',
                        'context' => InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED,
                        'title' => 'شروط بيع موسعة',
                        'content' => "السطر التلقائي الأول\nالسطر الإضافي الثاني\nالسطر الإضافي الثالث",
                        'show_on_invoice' => '1',
                    ],
                ],
                'default_template_keys' => [
                    InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED => 'retail-custom',
                ],
            ]);

        $response->assertRedirect(route('admin.system-settings.invoice-terms.edit', [], false));
        $settings = BranchInvoiceTermsSetting::query()->where('branch_id', $admin->branch_id)->first();

        $this->assertNotNull($settings);
        $this->assertSame(
            "السطر التلقائي الأول\nالسطر الإضافي الثاني\nالسطر الإضافي الثالث",
            data_get($settings?->templates, '0.content'),
        );
    }

    public function test_invoice_terms_settings_are_isolated_per_branch(): void
    {
        $firstAdmin = $this->createAdminUser([
            'employee.system_settings.show',
            'employee.system_settings.edit',
        ], 'first-admin-invoice-terms@example.com');
        $secondAdmin = $this->createAdminUser([
            'employee.system_settings.show',
            'employee.system_settings.edit',
        ], 'second-admin-invoice-terms@example.com');

        BranchInvoiceTermsSetting::query()->create([
            'branch_id' => $firstAdmin->branch_id,
            'templates' => [[
                'key' => 'first-retail',
                'context' => InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED,
                'title' => 'قالب الفرع الأول',
                'content' => "سطر أول للفرع الأول\nسطر ثان للفرع الأول",
                'show_on_invoice' => true,
            ]],
            'default_template_keys' => [
                InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED => 'first-retail',
            ],
        ]);

        BranchInvoiceTermsSetting::query()->create([
            'branch_id' => $secondAdmin->branch_id,
            'templates' => [[
                'key' => 'second-retail',
                'context' => InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED,
                'title' => 'قالب الفرع الثاني',
                'content' => "سطر أول للفرع الثاني\nسطر ثان للفرع الثاني",
                'show_on_invoice' => true,
            ]],
            'default_template_keys' => [
                InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED => 'second-retail',
            ],
        ]);

        $firstResolvedTerms = app(InvoiceTermsService::class)
            ->forBranch((int) $firstAdmin->branch_id)
            ->defaultTerms(InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED);

        $this->assertSame("سطر أول للفرع الأول\nسطر ثان للفرع الأول", $firstResolvedTerms);

        $secondResolvedTerms = app(InvoiceTermsService::class)
            ->forBranch((int) $secondAdmin->branch_id)
            ->defaultTerms(InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED);

        $this->assertSame("سطر أول للفرع الثاني\nسطر ثان للفرع الثاني", $secondResolvedTerms);
    }

    public function test_branch_invoice_terms_override_legacy_global_defaults_when_resolving_snapshot(): void
    {
        SystemSetting::putValue('default_invoice_terms', "قيمة عامة قديمة\nيجب تجاوزها");

        $admin = $this->createAdminUser([
            'employee.system_settings.show',
            'employee.system_settings.edit',
        ], 'priority-admin-invoice-terms@example.com');

        BranchInvoiceTermsSetting::query()->create([
            'branch_id' => $admin->branch_id,
            'templates' => [[
                'key' => 'priority-retail',
                'context' => InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED,
                'title' => 'أولوية الفرع',
                'content' => "شروط الفرع الحالية\nهي التي تحفظ في الفاتورة",
                'show_on_invoice' => true,
            ]],
            'default_template_keys' => [
                InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED => 'priority-retail',
            ],
        ]);

        $resolvedSnapshot = app(InvoiceTermsService::class)
            ->forBranch((int) $admin->branch_id)
            ->resolveSnapshot(null, InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED, false);

        $this->assertSame("شروط الفرع الحالية\nهي التي تحفظ في الفاتورة", $resolvedSnapshot);
    }

    /**
     * فرع لم تُضبط شروطه بعد يقرأ الإعداد العام دون أن يُنشأ له صفّ خاص —
     * فلا تتجمّد عنده نسخة تمنع وصول أي تعديل لاحق.
     */
    public function test_branch_without_its_own_settings_falls_back_to_global_settings(): void
    {
        SystemSetting::putValue('invoice_terms_templates', json_encode([
            [
                'key' => 'global-retail',
                'context' => InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED,
                'title' => 'القالب العام',
                'content' => "سطر عام أول\nسطر عام ثان",
                'show_on_invoice' => true,
            ],
        ], JSON_UNESCAPED_UNICODE));
        SystemSetting::putValue('default_invoice_terms_template_keys', json_encode([
            InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED => 'global-retail',
        ], JSON_UNESCAPED_UNICODE));

        $admin = $this->createAdminUser([
            'employee.system_settings.show',
            'employee.system_settings.edit',
        ], 'bootstrap-admin-invoice-terms@example.com');

        $resolvedTerms = app(InvoiceTermsService::class)
            ->forBranch((int) $admin->branch_id)
            ->defaultTerms(InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED);

        $this->assertSame("سطر عام أول\nسطر عام ثان", $resolvedTerms);
        $this->assertDatabaseMissing('branch_invoice_terms_settings', [
            'branch_id' => $admin->branch_id,
        ]);
    }

    public function test_sales_a5_print_styles_do_not_clip_multiline_invoice_terms(): void
    {
        $branch = $this->createBranch('فرع طباعة A5', 'sales-a5-branch@example.com', '333444555');
        $user = $this->createUser($branch, 'sales-a5-user@example.com');
        $invoice = $this->createInvoice($branch, $user, 'sale', [
            'sale_type' => 'simplified',
        ]);

        BranchInvoiceTermsSetting::query()->create([
            'branch_id' => $branch->id,
            'templates' => [[
                'key' => 'a5-multiline',
                'context' => InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED,
                'title' => 'شروط متعددة الأسطر',
                'content' => "السطر الأول\nالسطر الثاني\nالسطر الثالث",
                'show_on_invoice' => true,
            ]],
            'default_template_keys' => [
                InvoiceTermsService::CONTEXT_SALES_SIMPLIFIED => 'a5-multiline',
            ],
        ]);

        SystemSetting::putValue('invoice_print_format', 'a5');
        SystemSetting::putValue('invoice_print_template', 'classic');
        SystemSetting::putValue('invoice_print_orientation', 'portrait');

        $response = $this
            ->actingAs($user, 'admin-web')
            ->get(route('sales.show', ['id' => $invoice->id], false));

        $response->assertOk();
        $response->assertSee('max-height: none;', false);
        $response->assertSee('overflow: visible;', false);
        $response->assertSee('السطر الأول / السطر الثاني / السطر الثالث');
    }

    public function test_purchases_print_page_uses_the_branch_purchase_terms(): void
    {
        $branch = $this->createBranch('فرع المشتريات', 'purchases-branch@example.com', '222222222');
        $user = $this->createUser($branch, 'purchases-user@example.com');
        $supplierId = DB::table('customers')->insertGetId([
            'name' => 'مورد الاختبار',
            'type' => 'supplier',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invoice = $this->createInvoice($branch, $user, 'purchase', [
            'customer_id' => $supplierId,
            'supplier_bill_number' => 'SUP-1001',
        ]);

        BranchInvoiceTermsSetting::query()->create([
            'branch_id' => $branch->id,
            'templates' => [[
                'key' => 'branch-purchase',
                'context' => InvoiceTermsService::CONTEXT_PURCHASES,
                'title' => 'شروط شراء الفرع',
                'content' => "يتم اعتماد الوزن بعد الفحص\nوالدفع حسب الحساب البنكي المحدد",
                'show_on_invoice' => true,
            ]],
            'default_template_keys' => [
                InvoiceTermsService::CONTEXT_PURCHASES => 'branch-purchase',
            ],
        ]);

        $response = $this
            ->actingAs($user, 'admin-web')
            ->get(route('purchases.show', ['id' => $invoice->id], false));

        $response->assertOk();
        $response->assertSee('شروط الفاتورة');
        $response->assertSee('يتم اعتماد الوزن بعد الفحص');
        $response->assertSee('والدفع حسب الحساب البنكي المحدد');
        $response->assertSee('print-brand-logo', false);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function createAdminUser(array $permissions = [], string $email = 'admin-invoice-terms@example.com'): User
    {
        $branchToken = substr(md5($email), 0, 8);
        $branch = $this->createBranch(
            'الفرع الرئيسي',
            'main-branch-' . $branchToken . '@example.com',
            '12345' . substr($branchToken, 0, 4)
        );

        $role = Role::query()->where('guard_name', 'admin-web')->first();

        if (! $role instanceof Role) {
            $role = Role::create([
                'name' => ['ar' => 'مدير النظام', 'en' => 'System Admin'],
                'guard_name' => 'admin-web',
            ]);
        }

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'admin-web',
            ]);

            $role->givePermissionTo($permission);
        }

        $user = $this->createUser($branch, $email);
        $user->assignRole($role);

        return $user;
    }

    private function createBranch(string $name, string $email, string $phone): Branch
    {
        return Branch::create([
            'name' => ['ar' => $name, 'en' => $name],
            'email' => $email,
            'phone' => $phone,
            'tax_number' => str_pad((string) random_int(1, 999999999999999), 15, '0', STR_PAD_LEFT),
            'commercial_register' => str_pad((string) random_int(1, 9999999999), 10, '0', STR_PAD_LEFT),
            'short_address' => 'الرياض',
            'region' => 'الرياض',
            'city' => 'الرياض',
            'district' => 'الملز',
            'street_name' => 'الشارع الرئيسي',
            'building_number' => '1234',
            'plot_identification' => '5678',
            'country' => 'SA',
            'postal_code' => '12345',
            'status' => true,
        ]);
    }

    private function createUser(Branch $branch, string $email): User
    {
        return User::create([
            'name' => strtok($email, '@'),
            'email' => $email,
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'profile_pic' => 'default.png',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createInvoice(Branch $branch, User $user, string $type, array $attributes = []): Invoice
    {
        return Invoice::create(array_merge([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'type' => $type,
            'sale_type' => 'simplified',
            'payment_type' => 'cash',
            'date' => now()->format('Y-m-d'),
            'time' => now()->format('H:i:s'),
            'lines_total' => 100,
            'discount_total' => 0,
            'lines_total_after_discount' => 100,
            'taxes_total' => 15,
            'net_total' => 115,
        ], $attributes));
    }
}
