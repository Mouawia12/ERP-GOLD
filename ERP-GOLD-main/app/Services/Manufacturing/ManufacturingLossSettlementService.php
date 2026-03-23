<?php

namespace App\Services\Manufacturing;

use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\User;
use App\Services\JournalEntriesService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManufacturingLossSettlementService
{
    public function __construct(
        private readonly ManufacturingReceiptService $manufacturingReceiptService,
    ) {
    }

    public function loadOrder(int $orderId): Invoice
    {
        return $this->manufacturingReceiptService->loadOrder($orderId);
    }

    public function loadSettlement(int $invoiceId): Invoice
    {
        $invoice = $this->manufacturingReceiptService->loadInvoice($invoiceId);

        abort_unless($invoice->type === 'manufacturing_loss_settlement', 404);

        return $invoice->loadMissing([
            'branch',
            'user',
            'customer',
            'account',
            'parent',
            'manufacturingLossSettlementLines.item',
            'manufacturingLossSettlementLines.carat',
            'manufacturingLossSettlementLines.goldCaratType',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(Invoice $order, array $payload, User $user): Invoice
    {
        $order = $this->loadOrder($order->id);
        $lines = $this->normalizedLines($order, $payload);
        $settlementDate = Carbon::parse((string) $payload['bill_date']);

        return DB::transaction(function () use ($order, $user, $lines, $payload, $settlementDate) {
            $invoice = Invoice::create([
                'financial_year' => $order->financial_year,
                'branch_id' => $order->branch_id,
                'warehouse_id' => $order->warehouse_id,
                'customer_id' => $order->customer_id,
                'bill_client_name' => $order->bill_client_name,
                'bill_client_phone' => $order->bill_client_phone,
                'bill_client_identity_number' => $order->bill_client_identity_number,
                'parent_id' => $order->id,
                'type' => 'manufacturing_loss_settlement',
                'account_id' => (int) $payload['account_id'],
                'notes' => $payload['notes'] ?? '',
                'date' => $settlementDate->format('Y-m-d'),
                'time' => $settlementDate->format('H:i:s'),
                'lines_total' => round($lines->sum('line_total'), 2),
                'discount_total' => 0,
                'lines_total_after_discount' => round($lines->sum('line_total'), 2),
                'taxes_total' => 0,
                'net_total' => round($lines->sum('line_total'), 2),
                'user_id' => $user->id,
            ]);

            $invoice->manufacturingLossSettlementLines()->createMany($lines->map(function (array $line) use ($settlementDate) {
                return [
                    'parent_detail_id' => $line['parent_detail_id'],
                    'item_id' => $line['item']->id,
                    'gold_carat_id' => $line['item']->gold_carat_id,
                    'gold_carat_type_id' => $line['item']->gold_carat_type_id,
                    'settlement_type' => $line['settlement_type'],
                    'date' => $settlementDate->format('Y-m-d'),
                    'settled_quantity' => $line['quantity'],
                    'settled_weight' => $line['weight'],
                    'unit_cost' => $line['unit_cost'],
                    'line_total' => $line['line_total'],
                    'notes' => $line['line_notes'],
                ];
            })->all());

            JournalEntriesService::invoiceGenerateJournalEntries($invoice, [
                [
                    'account_id' => $invoice->account_id,
                    'debit' => round((float) $lines->sum('line_total'), 2),
                    'credit' => 0,
                    'document_date' => $invoice->date,
                ],
                [
                    'account_id' => $order->account_id,
                    'debit' => 0,
                    'credit' => round((float) $lines->sum('line_total'), 2),
                    'document_date' => $invoice->date,
                ],
            ]);

            return $invoice->load([
                'parent',
                'branch',
                'customer',
                'account',
                'user',
                'manufacturingLossSettlementLines.item',
                'manufacturingLossSettlementLines.carat',
                'manufacturingLossSettlementLines.goldCaratType',
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return Collection<int, array<string, mixed>>
     */
    private function normalizedLines(Invoice $order, array $payload): Collection
    {
        $progressByDetail = $this->manufacturingReceiptService->lineProgress($order)->keyBy('detail_id');

        $lineInputs = collect($payload['parent_detail_id'] ?? [])
            ->map(function ($detailId, $index) use ($payload) {
                return [
                    'parent_detail_id' => $detailId,
                    'settlement_type' => $payload['settlement_type'][$index] ?? 'natural_loss',
                    'quantity' => round((float) ($payload['quantity'][$index] ?? 0), 3),
                    'weight' => round((float) ($payload['weight'][$index] ?? 0), 3),
                    'line_notes' => trim((string) ($payload['line_notes'][$index] ?? '')),
                ];
            })
            ->filter(fn (array $line) => filled($line['parent_detail_id']) && $line['weight'] > 0)
            ->values();

        if ($lineInputs->isEmpty()) {
            throw ValidationException::withMessages([
                'parent_detail_id' => ['يجب إدخال سطر تسوية واحد على الأقل بوزن صحيح.'],
            ]);
        }

        return $lineInputs->map(function (array $line) use ($order, $progressByDetail) {
            /** @var InvoiceDetail|null $parentDetail */
            $parentDetail = $order->details->firstWhere('id', (int) $line['parent_detail_id']);
            $progress = $progressByDetail->get((int) $line['parent_detail_id']);

            if (! $parentDetail || ! $progress) {
                throw ValidationException::withMessages([
                    'parent_detail_id' => ['أحد سطور التسوية لا يتبع أمر التصنيع المحدد.'],
                ]);
            }

            if (! in_array($line['settlement_type'], ['natural_loss', 'final_damage', 'review_difference'], true)) {
                throw ValidationException::withMessages([
                    'settlement_type' => ['نوع التسوية المحدد غير صالح.'],
                ]);
            }

            if ($line['quantity'] > ((float) $progress['remaining_quantity'] + 0.0001)) {
                throw ValidationException::withMessages([
                    'quantity' => [sprintf('الكمية المسوّاة للصنف %s تتجاوز الكمية المتبقية في أمر التصنيع.', $progress['item_title'])],
                ]);
            }

            if ($line['weight'] > ((float) $progress['remaining_weight'] + 0.0001)) {
                throw ValidationException::withMessages([
                    'weight' => [sprintf('الوزن المسوّى للصنف %s يتجاوز الوزن المتبقي في أمر التصنيع.', $progress['item_title'])],
                ]);
            }

            $unitCost = round((float) $parentDetail->unit_cost, 4);

            return [
                'parent_detail_id' => $parentDetail->id,
                'item' => $parentDetail->item,
                'settlement_type' => $line['settlement_type'],
                'quantity' => $line['quantity'],
                'weight' => $line['weight'],
                'unit_cost' => $unitCost,
                'line_total' => round($line['weight'] * $unitCost, 2),
                'line_notes' => $line['line_notes'] !== '' ? $line['line_notes'] : null,
            ];
        })->values();
    }
}
