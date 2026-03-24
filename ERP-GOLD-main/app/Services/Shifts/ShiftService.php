<?php

namespace App\Services\Shifts;

use App\Models\Branch;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ShiftService
{
    public function currentForUser(User $user): ?Shift
    {
        return Shift::with(['branch', 'user'])
            ->open()
            ->where('user_id', $user->id)
            ->latest('opened_at')
            ->first();
    }

    public function open(User $user, int $branchId, float $openingCash = 0, ?string $notes = null): Shift
    {
        $branch = Branch::findOrFail($branchId);

        if (! $this->canManageAcrossBranches($user) && (int) $user->branch_id !== (int) $branch->id) {
            throw ValidationException::withMessages([
                'branch_id' => ['لا يمكنك فتح شفت على فرع غير مخصص لك.'],
            ]);
        }

        if ($this->currentForUser($user)) {
            throw ValidationException::withMessages([
                'shift' => ['يوجد شفت مفتوح بالفعل لهذا المستخدم.'],
            ]);
        }

        return Shift::create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'status' => 'open',
            'opened_at' => now(),
            'opening_cash' => round($openingCash, 2),
            'opening_notes' => $notes,
        ]);
    }

    public function requireActiveShift(User $user, int $branchId): Shift
    {
        $shift = Shift::query()
            ->open()
            ->where('user_id', $user->id)
            ->where('branch_id', $branchId)
            ->latest('opened_at')
            ->first();

        if (! $shift) {
            throw ValidationException::withMessages([
                'shift' => ['يجب فتح شفت نشط على هذا الفرع قبل تسجيل العملية.'],
            ]);
        }

        return $shift;
    }

    public function close(Shift $shift, float $closingCash, ?string $notes = null): Shift
    {
        if (! $shift->is_open) {
            throw ValidationException::withMessages([
                'shift' => ['هذا الشفت مغلق بالفعل.'],
            ]);
        }

        $summary = $this->summary($shift);
        $expectedCash = $summary['expected_cash'];

        $shift->update([
            'status' => 'closed',
            'closed_at' => now(),
            'closing_cash' => round($closingCash, 2),
            'expected_cash' => round($expectedCash, 2),
            'cash_difference' => round($closingCash - $expectedCash, 2),
            'closing_notes' => $notes,
        ]);

        return $shift->fresh(['branch', 'user']);
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(Shift $shift): array
    {
        $invoiceRows = $shift->invoices()
            ->with('paymentLines')
            ->get();

        $voucherRows = $shift->financialVouchers()
            ->with('bankAccount')
            ->orderByDesc('date')
            ->get();

        $invoiceTotals = $this->normalizeInvoiceTotals($invoiceRows);
        $voucherTotals = $this->normalizeVoucherTotals($voucherRows);

        $salesCashTotal = $invoiceTotals['sale_cash_total'];
        $saleReturnsCashTotal = $invoiceTotals['sale_return_cash_total'];
        $purchaseCashTotal = $invoiceTotals['purchase_cash_total'];
        $purchaseReturnCashTotal = $invoiceTotals['purchase_return_cash_total'];
        $receiptsTotal = $voucherTotals['receipt_cash_total'];
        $paymentsTotal = $voucherTotals['payment_cash_total'];
        $expectedCash = round((float) $shift->opening_cash + $salesCashTotal + $receiptsTotal + $purchaseReturnCashTotal - $paymentsTotal - $purchaseCashTotal - $saleReturnsCashTotal, 2);

        return [
            'invoice_totals' => $invoiceTotals,
            'voucher_totals' => $voucherTotals,
            'expected_cash' => $expectedCash,
            'linked_invoices' => $shift->invoices()->with(['branch', 'user'])->orderByDesc('date')->orderByDesc('time')->get(),
            'linked_vouchers' => $voucherRows,
        ];
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array<string, float|int>
     */
    private function normalizeInvoiceTotals(Collection $rows): array
    {
        $totals = [
            'sale_total' => 0.0,
            'sale_cash_total' => 0.0,
            'sale_card_total' => 0.0,
            'sale_return_total' => 0.0,
            'sale_return_cash_total' => 0.0,
            'sale_return_non_cash_total' => 0.0,
            'purchase_total' => 0.0,
            'purchase_cash_total' => 0.0,
            'purchase_non_cash_total' => 0.0,
            'purchase_return_total' => 0.0,
            'purchase_return_cash_total' => 0.0,
            'purchase_return_non_cash_total' => 0.0,
            'invoice_count' => 0,
        ];

        foreach ($rows as $row) {
            $totalNet = round((float) $row->net_total, 2);
            $totals['invoice_count']++;

            if ($row->type === 'sale') {
                $totals['sale_total'] += $totalNet;
                $saleCashTotal = round((float) $row->cash_paid_total, 2);
                $saleNonCashTotal = round((float) $row->credit_card_paid_total + (float) $row->bank_transfer_paid_total, 2);
                $totals['sale_cash_total'] += $saleCashTotal;
                $totals['sale_card_total'] += $saleNonCashTotal;
            } elseif ($row->type === 'sale_return') {
                $totals['sale_return_total'] += $totalNet;
                $saleReturnCashTotal = round((float) $row->cash_paid_total, 2);
                $saleReturnNonCashTotal = round((float) $row->credit_card_paid_total + (float) $row->bank_transfer_paid_total, 2);
                $totals['sale_return_cash_total'] += $saleReturnCashTotal;
                $totals['sale_return_non_cash_total'] += $saleReturnNonCashTotal;
            } elseif ($row->type === 'purchase') {
                $totals['purchase_total'] += $totalNet;
                $purchaseCashTotal = round((float) $row->cash_paid_total, 2);
                $purchaseNonCashTotal = round((float) $row->credit_card_paid_total + (float) $row->bank_transfer_paid_total, 2);
                $totals['purchase_cash_total'] += $purchaseCashTotal;
                $totals['purchase_non_cash_total'] += $purchaseNonCashTotal;
            } elseif ($row->type === 'purchase_return') {
                $totals['purchase_return_total'] += $totalNet;
                $purchaseReturnCashTotal = round((float) $row->cash_paid_total, 2);
                $purchaseReturnNonCashTotal = round((float) $row->credit_card_paid_total + (float) $row->bank_transfer_paid_total, 2);
                $totals['purchase_return_cash_total'] += $purchaseReturnCashTotal;
                $totals['purchase_return_non_cash_total'] += $purchaseReturnNonCashTotal;
            }
        }

        foreach ($totals as $key => $value) {
            if ($key !== 'invoice_count') {
                $totals[$key] = round((float) $value, 2);
            }
        }

        return $totals;
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array<string, float|int>
     */
    private function normalizeVoucherTotals(Collection $rows): array
    {
        $totals = [
            'receipt_total' => 0.0,
            'payment_total' => 0.0,
            'receipt_cash_total' => 0.0,
            'receipt_non_cash_total' => 0.0,
            'payment_cash_total' => 0.0,
            'payment_non_cash_total' => 0.0,
            'voucher_count' => 0,
        ];

        foreach ($rows as $row) {
            $amount = round((float) $row->total_amount, 2);
            $isCashVoucher = ($row->payment_method ?? 'cash') === 'cash';
            $totals['voucher_count']++;

            if ($row->type === 'receipt') {
                $totals['receipt_total'] += $amount;
                if ($isCashVoucher) {
                    $totals['receipt_cash_total'] += $amount;
                } else {
                    $totals['receipt_non_cash_total'] += $amount;
                }
            } elseif ($row->type === 'payment') {
                $totals['payment_total'] += $amount;
                if ($isCashVoucher) {
                    $totals['payment_cash_total'] += $amount;
                } else {
                    $totals['payment_non_cash_total'] += $amount;
                }
            }
        }

        foreach ($totals as $key => $value) {
            if ($key !== 'voucher_count') {
                $totals[$key] = round((float) $value, 2);
            }
        }

        return $totals;
    }

    private function canManageAcrossBranches(User $user): bool
    {
        return $user->canAny([
            'employee.users.show',
            'employee.user_permissions.show',
            'employee.branches.show',
        ]);
    }
}
