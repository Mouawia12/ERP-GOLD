<?php

namespace App\Services\Payments;

use App\Models\BankAccount;
use App\Models\Invoice;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class InvoicePaymentService
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    public function normalizeSalesLines(array $payload, int $branchId, float $expectedTotal): array
    {
        $cashAmount = round((float) ($payload['cash'] ?? 0), 2);
        $legacyVisaAmount = round((float) ($payload['visa'] ?? 0), 2);
        $normalizedLines = [];

        if ($cashAmount > 0) {
            $normalizedLines[] = [
                'branch_id' => $branchId,
                'method_type' => 'cash',
                'bank_account_id' => null,
                'reference_no' => null,
                'terminal_name' => null,
                'notes' => null,
                'amount' => $cashAmount,
            ];
        }

        $paymentLines = collect($payload['payment_lines'] ?? [])
            ->filter(function ($line) {
                return is_array($line);
            })
            ->values();

        if ($paymentLines->isEmpty() && $legacyVisaAmount > 0) {
            $defaultBankAccount = $this->defaultBranchBankAccount($branchId);

            if (! $defaultBankAccount) {
                throw ValidationException::withMessages([
                    'payment_lines' => ['لا يمكن استخدام دفع غير نقدي قبل تعريف حساب بنكي نشط على الفرع.'],
                ]);
            }

            $paymentLines->push([
                'method_type' => 'credit_card',
                'bank_account_id' => $defaultBankAccount->id,
                'amount' => $legacyVisaAmount,
                'reference_no' => null,
                'terminal_name' => $defaultBankAccount->terminal_name,
            ]);
        }

        $normalizedNonCashLines = $paymentLines
            ->map(function ($line, $index) use ($branchId) {
                $amount = round((float) ($line['amount'] ?? 0), 2);

                if ($amount <= 0) {
                    return null;
                }

                $methodType = $line['method_type'] ?? null;
                if (! in_array($methodType, ['credit_card', 'bank_transfer'], true)) {
                    throw ValidationException::withMessages([
                        "payment_lines.$index.method_type" => ['طريقة الدفع البنكية غير صالحة.'],
                    ]);
                }

                $bankAccountId = (int) ($line['bank_account_id'] ?? 0);
                $bankAccount = BankAccount::query()
                    ->active()
                    ->where('branch_id', $branchId)
                    ->find($bankAccountId);

                if (! $bankAccount) {
                    throw ValidationException::withMessages([
                        "payment_lines.$index.bank_account_id" => ['الحساب البنكي المحدد غير صالح لهذا الفرع.'],
                    ]);
                }

                if ($methodType === 'credit_card' && ! $bankAccount->supports_credit_card) {
                    throw ValidationException::withMessages([
                        "payment_lines.$index.bank_account_id" => ['الحساب البنكي المحدد لا يدعم مدفوعات الشبكة.'],
                    ]);
                }

                if ($methodType === 'bank_transfer' && ! $bankAccount->supports_bank_transfer) {
                    throw ValidationException::withMessages([
                        "payment_lines.$index.bank_account_id" => ['الحساب البنكي المحدد لا يدعم التحويل البنكي.'],
                    ]);
                }

                return [
                    'branch_id' => $branchId,
                    'method_type' => $methodType,
                    'bank_account_id' => $bankAccount->id,
                    'reference_no' => trim((string) ($line['reference_no'] ?? '')) ?: null,
                    'terminal_name' => trim((string) ($line['terminal_name'] ?? $bankAccount->terminal_name ?? '')) ?: null,
                    'notes' => trim((string) ($line['notes'] ?? '')) ?: null,
                    'amount' => $amount,
                ];
            })
            ->filter()
            ->values()
            ->all();

        $normalizedLines = array_merge($normalizedLines, $normalizedNonCashLines);

        if (count($normalizedLines) === 0) {
            throw ValidationException::withMessages([
                'payment_lines' => ['يجب إدخال سطر دفع واحد على الأقل قبل حفظ الفاتورة.'],
            ]);
        }

        $paymentsTotal = round((float) collect($normalizedLines)->sum('amount'), 2);
        $expectedTotal = round($expectedTotal, 2);

        if (abs($paymentsTotal - $expectedTotal) > 0.01) {
            throw ValidationException::withMessages([
                'payment_lines' => ['إجمالي أسطر الدفع يجب أن يساوي صافي الفاتورة بالكامل.'],
            ]);
        }

        return $normalizedLines;
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    public function persist(Invoice $invoice, array $lines): void
    {
        $invoice->paymentLines()->delete();
        $invoice->paymentLines()->createMany($lines);
        $invoice->unsetRelation('paymentLines');
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    public function resolveStoredPaymentType(array $lines): string
    {
        $methodTypes = collect($lines)->pluck('method_type')->unique()->values();

        if ($methodTypes->count() === 1) {
            return (string) $methodTypes->first();
        }

        return collect($lines)->contains(fn ($line) => ($line['method_type'] ?? null) === 'cash')
            ? 'cash'
            : (string) $methodTypes->first();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function journalDebitLines(Invoice $invoice, int $safeAccountId, ?int $fallbackBankAccountId): array
    {
        $documentDate = $invoice->date;

        return $this->resolvedLinesCollection($invoice)
            ->map(function ($line) use ($safeAccountId, $fallbackBankAccountId, $documentDate) {
                $accountId = $line['method_type'] === 'cash'
                    ? $safeAccountId
                    : ($line['ledger_account_id'] ?: $fallbackBankAccountId);

                if (! $accountId) {
                    $this->throwMissingLedgerAccountValidation($line['method_type'] ?? null);
                }

                return [
                    'account_id' => $accountId,
                    'debit' => round((float) $line['amount'], 2),
                    'credit' => 0,
                    'document_date' => $documentDate,
                    'notes' => $line['notes'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function journalCreditLines(Invoice $invoice, int $safeAccountId, ?int $fallbackBankAccountId): array
    {
        $documentDate = $invoice->date;

        return $this->resolvedLinesCollection($invoice)
            ->map(function ($line) use ($safeAccountId, $fallbackBankAccountId, $documentDate) {
                $accountId = $line['method_type'] === 'cash'
                    ? $safeAccountId
                    : ($line['ledger_account_id'] ?: $fallbackBankAccountId);

                if (! $accountId) {
                    $this->throwMissingLedgerAccountValidation($line['method_type'] ?? null);
                }

                return [
                    'account_id' => $accountId,
                    'debit' => 0,
                    'credit' => round((float) $line['amount'], 2),
                    'document_date' => $documentDate,
                    'notes' => $line['notes'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, float>
     */
    public function totalsForInvoice(Invoice $invoice): array
    {
        $resolvedLines = $this->resolvedLinesCollection($invoice);

        return [
            'cash' => round((float) $resolvedLines->where('method_type', 'cash')->sum('amount'), 2),
            'credit_card' => round((float) $resolvedLines->where('method_type', 'credit_card')->sum('amount'), 2),
            'bank_transfer' => round((float) $resolvedLines->where('method_type', 'bank_transfer')->sum('amount'), 2),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function paymentBreakdown(Invoice $invoice): array
    {
        return $this->resolvedLinesCollection($invoice)
            ->map(function ($line) {
                return [
                    'method_type' => $line['method_type'],
                    'method_label' => $this->paymentTypeLabel($line['method_type']),
                    'amount' => round((float) $line['amount'], 2),
                    'bank_account_name' => $line['bank_account_name'],
                    'reference_no' => $line['reference_no'],
                    'terminal_name' => $line['terminal_name'],
                ];
            })
            ->values()
            ->all();
    }

    public function paymentTypeLabel(?string $paymentType): string
    {
        return match ($paymentType) {
            'cash' => 'نقدي',
            'credit_card' => 'شبكة / بطاقة',
            'bank_transfer' => 'تحويل بنكي',
            'mixed' => 'مختلط',
            default => $paymentType ?: '-',
        };
    }

    public function paymentTypeLabelForInvoice(Invoice $invoice): string
    {
        $methodTypes = $this->resolvedLinesCollection($invoice)->pluck('method_type')->unique()->values();

        if ($methodTypes->count() > 1) {
            return $this->paymentTypeLabel('mixed');
        }

        if ($methodTypes->count() === 1) {
            return $this->paymentTypeLabel((string) $methodTypes->first());
        }

        return $this->paymentTypeLabel($invoice->payment_type);
    }

    private function defaultBranchBankAccount(int $branchId): ?BankAccount
    {
        return BankAccount::query()
            ->active()
            ->where('branch_id', $branchId)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    private function resolvedLinesCollection(Invoice $invoice): Collection
    {
        $paymentLines = $invoice->relationLoaded('paymentLines')
            ? $invoice->paymentLines
            : $invoice->paymentLines()->with('bankAccount')->get();

        if ($paymentLines->isNotEmpty()) {
            return $paymentLines->map(function ($line) {
                return [
                    'method_type' => $line->method_type,
                    'amount' => round((float) $line->amount, 2),
                    'reference_no' => $line->reference_no,
                    'terminal_name' => $line->terminal_name,
                    'notes' => $line->notes,
                    'bank_account_name' => $line->bankAccount?->display_name,
                    'ledger_account_id' => $line->bankAccount?->ledger_account_id,
                ];
            });
        }

        return collect([[
            'method_type' => $invoice->payment_type ?: 'cash',
            'amount' => round((float) $invoice->net_total, 2),
            'reference_no' => null,
            'terminal_name' => null,
            'notes' => null,
            'bank_account_name' => null,
            'ledger_account_id' => null,
        ]]);
    }

    private function throwMissingLedgerAccountValidation(?string $methodType): never
    {
        $message = $methodType === 'cash'
            ? 'لا يوجد حساب صندوق/خزينة نقدية مربوط بإعدادات الحسابات لهذا الفرع.'
            : 'لا يوجد حساب محاسبي مربوط بوسيلة الدفع غير النقدية.';

        throw ValidationException::withMessages([
            'payment_lines' => [$message],
        ]);
    }
}
