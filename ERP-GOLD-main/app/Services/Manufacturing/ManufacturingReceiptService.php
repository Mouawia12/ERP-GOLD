<?php

namespace App\Services\Manufacturing;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\User;
use App\Services\JournalEntriesService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManufacturingReceiptService
{
    public function __construct(
        private readonly ManufacturingOrderService $manufacturingOrderService,
    ) {
    }

    public function loadOrder(int $orderId): Invoice
    {
        $order = $this->loadInvoice($orderId);

        if ($order->type !== 'manufacturing_order') {
            abort(404);
        }

        return $order;
    }

    public function loadInvoice(int $invoiceId): Invoice
    {
        $invoice = Invoice::query()
            ->with([
                'branch.accountSetting',
                'branch.warehouses',
                'user',
                'customer',
                'account',
                'details.item.defaultUnit',
                'details.carat',
                'details.goldCaratType',
                'manufacturingReceipts.user',
                'manufacturingReceipts.details.item',
                'manufacturingReceipts.details.carat',
                'manufacturingReceipts.details.goldCaratType',
                'manufacturingReturns.user',
                'manufacturingReturns.details.item',
                'manufacturingReturns.details.carat',
                'manufacturingReturns.details.goldCaratType',
                'manufacturingLossSettlements.user',
                'manufacturingLossSettlements.manufacturingLossSettlementLines.item',
                'manufacturingLossSettlements.manufacturingLossSettlementLines.carat',
                'manufacturingLossSettlements.manufacturingLossSettlementLines.goldCaratType',
                'parent.branch',
                'parent.customer',
                'parent.account',
                'parent.details.item',
                'parent.details.carat',
                'parent.details.goldCaratType',
            ])
            ->findOrFail($invoiceId);

        abort_unless(
            in_array($invoice->type, ['manufacturing_order', 'manufacturing_receipt', 'manufacturing_return', 'manufacturing_loss_settlement'], true),
            404
        );

        return $invoice;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function lineProgress(Invoice $order): Collection
    {
        $order->loadMissing([
            'details.item.defaultUnit',
            'details.carat',
            'details.goldCaratType',
            'manufacturingReceipts.details',
            'manufacturingReturns.details',
            'manufacturingLossSettlements.manufacturingLossSettlementLines',
        ]);

        $receivedByParent = DB::table('invoice_details')
            ->join('invoices', 'invoices.id', '=', 'invoice_details.invoice_id')
            ->selectRaw('invoice_details.parent_id, SUM(invoice_details.in_quantity) as received_quantity, SUM(invoice_details.in_weight) as received_weight')
            ->where('invoices.type', 'manufacturing_receipt')
            ->where('invoices.parent_id', $order->id)
            ->whereNotNull('invoice_details.parent_id')
            ->groupBy('invoice_details.parent_id')
            ->get()
            ->keyBy('parent_id');

        $settledByParent = DB::table('manufacturing_loss_settlement_lines')
            ->join('invoices', 'invoices.id', '=', 'manufacturing_loss_settlement_lines.invoice_id')
            ->selectRaw('manufacturing_loss_settlement_lines.parent_detail_id, SUM(manufacturing_loss_settlement_lines.settled_quantity) as settled_quantity, SUM(manufacturing_loss_settlement_lines.settled_weight) as settled_weight')
            ->where('invoices.type', 'manufacturing_loss_settlement')
            ->where('invoices.parent_id', $order->id)
            ->whereNotNull('manufacturing_loss_settlement_lines.parent_detail_id')
            ->groupBy('manufacturing_loss_settlement_lines.parent_detail_id')
            ->get()
            ->keyBy('parent_detail_id');

        $returnsByParent = DB::table('invoice_details')
            ->join('invoices', 'invoices.id', '=', 'invoice_details.invoice_id')
            ->selectRaw("
                invoice_details.parent_id,
                SUM(CASE WHEN invoices.manufacturing_return_direction = 'from_manufacturer' THEN invoice_details.in_quantity ELSE 0 END) as returned_to_branch_quantity,
                SUM(CASE WHEN invoices.manufacturing_return_direction = 'from_manufacturer' THEN invoice_details.in_weight ELSE 0 END) as returned_to_branch_weight,
                SUM(CASE WHEN invoices.manufacturing_return_direction = 'to_manufacturer' THEN invoice_details.out_quantity ELSE 0 END) as returned_to_manufacturer_quantity,
                SUM(CASE WHEN invoices.manufacturing_return_direction = 'to_manufacturer' THEN invoice_details.out_weight ELSE 0 END) as returned_to_manufacturer_weight
            ")
            ->where('invoices.type', 'manufacturing_return')
            ->where('invoices.parent_id', $order->id)
            ->whereNotNull('invoice_details.parent_id')
            ->groupBy('invoice_details.parent_id')
            ->get()
            ->keyBy('parent_id');

        return $order->details->map(function (InvoiceDetail $detail) use ($receivedByParent, $settledByParent, $returnsByParent, $order) {
            $received = $receivedByParent->get($detail->id);
            $settled = $settledByParent->get($detail->id);
            $returns = $returnsByParent->get($detail->id);
            $receivedQuantity = round((float) ($received->received_quantity ?? 0), 3);
            $receivedWeight = round((float) ($received->received_weight ?? 0), 3);
            $settledQuantity = round((float) ($settled->settled_quantity ?? 0), 3);
            $settledWeight = round((float) ($settled->settled_weight ?? 0), 3);
            $returnedToBranchQuantity = round((float) ($returns->returned_to_branch_quantity ?? 0), 3);
            $returnedToBranchWeight = round((float) ($returns->returned_to_branch_weight ?? 0), 3);
            $returnedToManufacturerQuantity = round((float) ($returns->returned_to_manufacturer_quantity ?? 0), 3);
            $returnedToManufacturerWeight = round((float) ($returns->returned_to_manufacturer_weight ?? 0), 3);
            $sentQuantity = round((float) $detail->out_quantity, 3);
            $sentWeight = round((float) $detail->out_weight, 3);
            $availableForReturnQuantity = round(max($receivedQuantity + $returnedToBranchQuantity - $returnedToManufacturerQuantity, 0), 3);
            $availableForReturnWeight = round(max($receivedWeight + $returnedToBranchWeight - $returnedToManufacturerWeight, 0), 3);

            return [
                'detail_id' => $detail->id,
                'item_id' => $detail->item_id,
                'item_title' => $detail->item?->title ?? '-',
                'carat_label' => $detail->carat?->title ?? '-',
                'gold_carat_type_label' => $detail->goldCaratType?->title ?? '-',
                'stock_actual_weight' => round((float) ($detail->stock_actual_weight ?? 0), 3),
                'sent_quantity' => $sentQuantity,
                'sent_weight' => $sentWeight,
                'received_quantity' => $receivedQuantity,
                'received_weight' => $receivedWeight,
                'returned_to_branch_quantity' => $returnedToBranchQuantity,
                'returned_to_branch_weight' => $returnedToBranchWeight,
                'returned_to_manufacturer_quantity' => $returnedToManufacturerQuantity,
                'returned_to_manufacturer_weight' => $returnedToManufacturerWeight,
                'settled_quantity' => $settledQuantity,
                'settled_weight' => $settledWeight,
                'available_for_return_quantity' => $availableForReturnQuantity,
                'available_for_return_weight' => $availableForReturnWeight,
                'remaining_quantity' => round(max($sentQuantity - $receivedQuantity - $returnedToBranchQuantity + $returnedToManufacturerQuantity - $settledQuantity, 0), 3),
                'remaining_weight' => round(max($sentWeight - $receivedWeight - $returnedToBranchWeight + $returnedToManufacturerWeight - $settledWeight, 0), 3),
                'current_branch_weight' => $this->manufacturingOrderService->availableWeightForBranch($detail->item, (int) $order->branch_id),
                'unit_cost' => round((float) $detail->unit_cost, 4),
                'line_value' => round((float) $detail->net_total, 2),
            ];
        })->values();
    }

    /**
     * @return array<string, float|int>
     */
    public function summary(Invoice $order): array
    {
        $progress = $this->lineProgress($order);

        return [
            'lines_count' => $progress->count(),
            'sent_quantity' => round((float) $progress->sum('sent_quantity'), 3),
            'sent_weight' => round((float) $progress->sum('sent_weight'), 3),
            'received_quantity' => round((float) $progress->sum('received_quantity'), 3),
            'received_weight' => round((float) $progress->sum('received_weight'), 3),
            'returned_to_branch_quantity' => round((float) $progress->sum('returned_to_branch_quantity'), 3),
            'returned_to_branch_weight' => round((float) $progress->sum('returned_to_branch_weight'), 3),
            'returned_to_manufacturer_quantity' => round((float) $progress->sum('returned_to_manufacturer_quantity'), 3),
            'returned_to_manufacturer_weight' => round((float) $progress->sum('returned_to_manufacturer_weight'), 3),
            'settled_quantity' => round((float) $progress->sum('settled_quantity'), 3),
            'settled_weight' => round((float) $progress->sum('settled_weight'), 3),
            'available_for_return_quantity' => round((float) $progress->sum('available_for_return_quantity'), 3),
            'available_for_return_weight' => round((float) $progress->sum('available_for_return_weight'), 3),
            'remaining_quantity' => round((float) $progress->sum('remaining_quantity'), 3),
            'remaining_weight' => round((float) $progress->sum('remaining_weight'), 3),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function receiptSummaries(Invoice $order): Collection
    {
        $order->loadMissing('manufacturingReceipts.user', 'manufacturingReceipts.details');

        return $order->manufacturingReceipts
            ->sortByDesc('id')
            ->values()
            ->map(function (Invoice $receipt) {
                return [
                    'id' => $receipt->id,
                    'bill_number' => $receipt->bill_number,
                    'date' => $receipt->date,
                    'time' => $receipt->time,
                    'user_name' => $receipt->user?->name ?? '-',
                    'total_quantity' => round((float) $receipt->details->sum('in_quantity'), 3),
                    'total_weight' => round((float) $receipt->details->sum('in_weight'), 3),
                    'total_value' => round((float) $receipt->net_total, 2),
                ];
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function settlementSummaries(Invoice $order): Collection
    {
        $order->loadMissing('manufacturingLossSettlements.user', 'manufacturingLossSettlements.manufacturingLossSettlementLines');

        return $order->manufacturingLossSettlements
            ->sortByDesc('id')
            ->values()
            ->map(function (Invoice $settlement) {
                return [
                    'id' => $settlement->id,
                    'bill_number' => $settlement->bill_number,
                    'date' => $settlement->date,
                    'time' => $settlement->time,
                    'user_name' => $settlement->user?->name ?? '-',
                    'total_quantity' => round((float) $settlement->manufacturingLossSettlementLines->sum('settled_quantity'), 3),
                    'total_weight' => round((float) $settlement->manufacturingLossSettlementLines->sum('settled_weight'), 3),
                    'total_value' => round((float) $settlement->net_total, 2),
                ];
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function returnSummaries(Invoice $order): Collection
    {
        $order->loadMissing('manufacturingReturns.user', 'manufacturingReturns.details');

        return $order->manufacturingReturns
            ->sortByDesc('id')
            ->values()
            ->map(function (Invoice $return) {
                $isFromManufacturer = $return->manufacturing_return_direction === 'from_manufacturer';

                return [
                    'id' => $return->id,
                    'bill_number' => $return->bill_number,
                    'direction' => $return->manufacturing_return_direction,
                    'direction_label' => $return->manufacturing_return_direction_label,
                    'date' => $return->date,
                    'time' => $return->time,
                    'user_name' => $return->user?->name ?? '-',
                    'total_quantity' => round((float) $return->details->sum($isFromManufacturer ? 'in_quantity' : 'out_quantity'), 3),
                    'total_weight' => round((float) $return->details->sum($isFromManufacturer ? 'in_weight' : 'out_weight'), 3),
                    'total_value' => round((float) $return->net_total, 2),
                ];
            });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(Invoice $order, array $payload, User $user): Invoice
    {
        $order = $this->loadOrder($order->id);
        $orderBranch = $order->branch;

        if (! $orderBranch instanceof Branch) {
            throw ValidationException::withMessages([
                'branch_id' => ['الفرع المرتبط بأمر التصنيع غير صالح.'],
            ]);
        }

        $lines = $this->normalizedLines($order, $payload, $orderBranch);
        $receiptDate = Carbon::parse((string) $payload['bill_date']);

        return DB::transaction(function () use ($order, $user, $lines, $receiptDate) {
            $receipt = Invoice::create([
                'financial_year' => $order->financial_year,
                'branch_id' => $order->branch_id,
                'warehouse_id' => $order->warehouse_id ?: $order->branch?->warehouses?->first()?->id,
                'customer_id' => $order->customer_id,
                'bill_client_name' => $order->bill_client_name,
                'bill_client_phone' => $order->bill_client_phone,
                'bill_client_identity_number' => $order->bill_client_identity_number,
                'parent_id' => $order->id,
                'type' => 'manufacturing_receipt',
                'account_id' => $order->account_id,
                'notes' => $payload['notes'] ?? '',
                'date' => $receiptDate->format('Y-m-d'),
                'time' => $receiptDate->format('H:i:s'),
                'lines_total' => round($lines->sum('net_total'), 2),
                'discount_total' => 0,
                'lines_total_after_discount' => round($lines->sum('net_total'), 2),
                'taxes_total' => 0,
                'net_total' => round($lines->sum('net_total'), 2),
                'user_id' => $user->id,
            ]);

            $receipt->details()->createMany($lines->map(function (array $line) use ($receiptDate) {
                return [
                    'warehouse_id' => $line['warehouse_id'],
                    'parent_id' => $line['parent_detail_id'],
                    'item_id' => $line['item']->id,
                    'gold_carat_id' => $line['item']->gold_carat_id,
                    'gold_carat_type_id' => $line['item']->gold_carat_type_id,
                    'date' => $receiptDate->format('Y-m-d'),
                    'in_quantity' => $line['quantity'],
                    'out_quantity' => 0,
                    'in_weight' => $line['weight'],
                    'out_weight' => 0,
                    'unit_cost' => $line['unit_cost'],
                    'unit_price' => $line['unit_cost'],
                    'unit_discount' => 0,
                    'unit_tax' => 0,
                    'unit_tax_rate' => 0,
                    'unit_tax_id' => null,
                    'line_total' => $line['net_total'],
                    'line_discount' => 0,
                    'line_tax' => 0,
                    'net_total' => $line['net_total'],
                    'stock_actual_weight' => $line['available_weight'],
                ];
            })->all());

            JournalEntriesService::invoiceGenerateJournalEntries($receipt, $this->journalLines($receipt, $lines));

            return $receipt->load([
                'parent',
                'branch',
                'customer',
                'account',
                'user',
                'details.item',
                'details.carat',
                'details.goldCaratType',
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return Collection<int, array<string, mixed>>
     */
    private function normalizedLines(Invoice $order, array $payload, Branch $branch): Collection
    {
        $progressByDetail = $this->lineProgress($order)->keyBy('detail_id');
        $accountSetting = $branch->accountSetting;

        if (! $accountSetting) {
            throw ValidationException::withMessages([
                'bill_date' => ['لا توجد إعدادات حسابات مرتبطة بهذا الفرع.'],
            ]);
        }

        $lineInputs = collect($payload['parent_detail_id'] ?? [])
            ->map(function ($detailId, $index) use ($payload) {
                return [
                    'parent_detail_id' => $detailId,
                    'quantity' => round((float) ($payload['quantity'][$index] ?? 0), 3),
                    'weight' => round((float) ($payload['weight'][$index] ?? 0), 3),
                ];
            })
            ->filter(fn (array $line) => filled($line['parent_detail_id']) && $line['quantity'] > 0 && $line['weight'] > 0)
            ->values();

        if ($lineInputs->isEmpty()) {
            throw ValidationException::withMessages([
                'parent_detail_id' => ['يجب إدخال سطر استلام واحد على الأقل بكمية ووزن صحيحين.'],
            ]);
        }

        return $lineInputs->map(function (array $line) use ($order, $progressByDetail, $branch, $accountSetting) {
            /** @var InvoiceDetail|null $parentDetail */
            $parentDetail = $order->details->firstWhere('id', (int) $line['parent_detail_id']);
            $progress = $progressByDetail->get((int) $line['parent_detail_id']);

            if (! $parentDetail || ! $progress) {
                throw ValidationException::withMessages([
                    'parent_detail_id' => ['أحد سطور الاستلام لا يتبع أمر التصنيع المحدد.'],
                ]);
            }

            if ($line['quantity'] > ((float) $progress['remaining_quantity'] + 0.0001)) {
                throw ValidationException::withMessages([
                    'quantity' => [sprintf('الكمية المستلمة للصنف %s تتجاوز الكمية المتبقية في أمر التصنيع.', $progress['item_title'])],
                ]);
            }

            if ($line['weight'] > ((float) $progress['remaining_weight'] + 0.0001)) {
                throw ValidationException::withMessages([
                    'weight' => [sprintf('الوزن المستلم للصنف %s يتجاوز الوزن المتبقي في أمر التصنيع.', $progress['item_title'])],
                ]);
            }

            $stockAccountField = 'stock_account_' . ($parentDetail->goldCaratType?->key ?? 'crafted');
            $stockAccountId = $accountSetting->{$stockAccountField} ?? null;

            if (! $stockAccountId) {
                throw ValidationException::withMessages([
                    'bill_date' => [sprintf('لا يوجد حساب مخزون معرف للصنف %s في إعدادات الفرع.', $progress['item_title'])],
                ]);
            }

            $availableWeight = $this->manufacturingOrderService->availableWeightForBranch($parentDetail->item, $branch->id);
            $unitCost = round((float) $parentDetail->unit_cost, 4);

            return [
                'parent_detail_id' => $parentDetail->id,
                'item' => $parentDetail->item,
                'quantity' => $line['quantity'],
                'weight' => $line['weight'],
                'unit_cost' => $unitCost,
                'net_total' => round($line['weight'] * $unitCost, 2),
                'available_weight' => $availableWeight,
                'stock_account_id' => (int) $stockAccountId,
                'warehouse_id' => $order->warehouse_id ?: $branch->warehouses->first()?->id,
            ];
        })->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $lines
     * @return array<int, array<string, mixed>>
     */
    private function journalLines(Invoice $receipt, Collection $lines): array
    {
        $journalLines = [];
        $documentDate = $receipt->date;

        foreach ($lines->groupBy('stock_account_id') as $stockAccountId => $accountLines) {
            $debitTotal = round((float) $accountLines->sum('net_total'), 2);

            if ($debitTotal <= 0) {
                continue;
            }

            $journalLines[] = [
                'account_id' => (int) $stockAccountId,
                'debit' => $debitTotal,
                'credit' => 0,
                'document_date' => $documentDate,
            ];
        }

        $creditTotal = round((float) $lines->sum('net_total'), 2);

        if ($creditTotal > 0) {
            $journalLines[] = [
                'account_id' => $receipt->account_id,
                'debit' => 0,
                'credit' => $creditTotal,
                'document_date' => $documentDate,
            ];
        }

        return $journalLines;
    }
}
