<?php

namespace App\Services\Invoices;

use App\Models\Invoice;
use App\Models\InvoiceCounter;
use Illuminate\Support\Facades\DB;

class InvoiceNumberService
{
    /**
     * @return array<string, string>
     */
    public function assign(Invoice $invoice): array
    {
        if (!blank($invoice->bill_number) && !blank($invoice->serial)) {
            return [
                'bill_number' => $invoice->bill_number,
                'serial' => $invoice->serial,
            ];
        }

        if (blank($invoice->user_id) || blank($invoice->branch_id) || blank($invoice->type)) {
            return $this->assignLegacyNumber($invoice);
        }

        return DB::transaction(function () use ($invoice) {
            $counter = InvoiceCounter::query()
                ->where('user_id', $invoice->user_id)
                ->where('branch_id', $invoice->branch_id)
                ->where('type', $invoice->type)
                ->lockForUpdate()
                ->first();

            if (!$counter) {
                $counter = InvoiceCounter::create([
                    'user_id' => $invoice->user_id,
                    'branch_id' => $invoice->branch_id,
                    'type' => $invoice->type,
                    'last_number' => 0,
                ]);
            }

            $counter->increment('last_number');
            $counter->refresh();

            $serial = str_pad((string) $counter->last_number, 5, '0', STR_PAD_LEFT);
            $billNumber = implode('-', [
                $this->prefix($invoice->type),
                $invoice->branch_id,
                $invoice->user_id,
                $serial,
            ]);

            return [
                'bill_number' => $billNumber,
                'serial' => $serial,
            ];
        });
    }

    protected function prefix(string $type): string
    {
        return match ($type) {
            'sale' => 'S',
            'purchase' => 'P',
            'sale_return' => 'SR',
            'purchase_return' => 'PR',
            'initial_quantities' => 'INQ',
            'stock_settlements' => 'SS',
            'stock_movement' => 'SM',
            default => strtoupper(substr($type, 0, 3)),
        };
    }

    /**
     * @return array<string, string>
     */
    protected function assignLegacyNumber(Invoice $invoice): array
    {
        $lastInvoice = Invoice::query()
            ->where('branch_id', $invoice->branch_id)
            ->where('type', $invoice->type)
            ->orderByDesc('id')
            ->first();

        $serial = str_pad((string) ((int) ($lastInvoice?->serial ?? 0) + 1), 5, '0', STR_PAD_LEFT);
        $billNumber = implode('-', array_filter([
            $this->prefix((string) $invoice->type),
            $invoice->branch_id,
            $serial,
        ], fn ($segment) => !blank($segment)));

        return [
            'bill_number' => $billNumber,
            'serial' => $serial,
        ];
    }
}
