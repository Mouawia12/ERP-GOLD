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

class ManufacturingReturnService
{
    public function __construct(
        private readonly ManufacturingOrderService $manufacturingOrderService,
        private readonly ManufacturingReceiptService $manufacturingReceiptService,
    ) {
    }

    public function loadOrder(int $orderId): Invoice
    {
        return $this->manufacturingReceiptService->loadOrder($orderId);
    }

    public function loadReturn(int $invoiceId): Invoice
    {
        $invoice = $this->manufacturingReceiptService->loadInvoice($invoiceId);

        if ($invoice->type !== 'manufacturing_return') {
            abort(404);
        }

        return $invoice;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(Invoice $order, array $payload, User $user): Invoice
    {
        $order = $this->loadOrder($order->id);
        $branch = $order->branch;

        if (! $branch instanceof Branch) {
            throw ValidationException::withMessages([
                'bill_date' => ['الفرع المرتبط بأمر التصنيع غير صالح.'],
            ]);
        }

        $direction = (string) ($payload['return_direction'] ?? 'from_manufacturer');
        $lines = $this->normalizedLines($order, $payload, $branch, $direction);
        $returnDate = Carbon::parse((string) $payload['bill_date']);

        return DB::transaction(function () use ($order, $user, $direction, $lines, $returnDate) {
            $invoice = Invoice::create([
                'financial_year' => $order->financial_year,
                'branch_id' => $order->branch_id,
                'warehouse_id' => $order->warehouse_id ?: $order->branch?->warehouses?->first()?->id,
                'customer_id' => $order->customer_id,
                'bill_client_name' => $order->bill_client_name,
                'bill_client_phone' => $order->bill_client_phone,
                'bill_client_identity_number' => $order->bill_client_identity_number,
                'parent_id' => $order->id,
                'type' => 'manufacturing_return',
                'manufacturing_return_direction' => $direction,
                'account_id' => $order->account_id,
                'notes' => $payload['notes'] ?? '',
                'date' => $returnDate->format('Y-m-d'),
                'time' => $returnDate->format('H:i:s'),
                'lines_total' => round($lines->sum('net_total'), 2),
                'discount_total' => 0,
                'lines_total_after_discount' => round($lines->sum('net_total'), 2),
                'taxes_total' => 0,
                'net_total' => round($lines->sum('net_total'), 2),
                'user_id' => $user->id,
            ]);

            $invoice->details()->createMany($lines->map(function (array $line) use ($returnDate, $direction) {
                $isFromManufacturer = $direction === 'from_manufacturer';

                return [
                    'warehouse_id' => $line['warehouse_id'],
                    'parent_id' => $line['parent_detail_id'],
                    'item_id' => $line['item']->id,
                    'gold_carat_id' => $line['item']->gold_carat_id,
                    'gold_carat_type_id' => $line['item']->gold_carat_type_id,
                    'date' => $returnDate->format('Y-m-d'),
                    'in_quantity' => $isFromManufacturer ? $line['quantity'] : 0,
                    'out_quantity' => $isFromManufacturer ? 0 : $line['quantity'],
                    'in_weight' => $isFromManufacturer ? $line['weight'] : 0,
                    'out_weight' => $isFromManufacturer ? 0 : $line['weight'],
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

            JournalEntriesService::invoiceGenerateJournalEntries($invoice, $this->journalLines($invoice, $lines, $direction));

            return $invoice->load([
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
    private function normalizedLines(Invoice $order, array $payload, Branch $branch, string $direction): Collection
    {
        if (! in_array($direction, ['from_manufacturer', 'to_manufacturer'], true)) {
            throw ValidationException::withMessages([
                'return_direction' => ['اتجاه الإرجاع غير صالح.'],
            ]);
        }

        $progressByDetail = $this->manufacturingReceiptService->lineProgress($order)->keyBy('detail_id');
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
                'parent_detail_id' => ['يجب إدخال سطر إرجاع واحد على الأقل بكمية ووزن صحيحين.'],
            ]);
        }

        return $lineInputs->map(function (array $line) use ($order, $progressByDetail, $branch, $accountSetting, $direction) {
            /** @var InvoiceDetail|null $parentDetail */
            $parentDetail = $order->details->firstWhere('id', (int) $line['parent_detail_id']);
            $progress = $progressByDetail->get((int) $line['parent_detail_id']);

            if (! $parentDetail || ! $progress) {
                throw ValidationException::withMessages([
                    'parent_detail_id' => ['أحد سطور الإرجاع لا يتبع أمر التصنيع المحدد.'],
                ]);
            }

            $availableWeight = $this->manufacturingOrderService->availableWeightForBranch($parentDetail->item, $branch->id);
            $limitQuantity = $direction === 'from_manufacturer'
                ? (float) $progress['remaining_quantity']
                : (float) $progress['available_for_return_quantity'];
            $limitWeight = $direction === 'from_manufacturer'
                ? (float) $progress['remaining_weight']
                : (float) $progress['available_for_return_weight'];

            if ($line['quantity'] > ($limitQuantity + 0.0001)) {
                throw ValidationException::withMessages([
                    'quantity' => [sprintf('الكمية المحددة للصنف %s تتجاوز الحد المسموح لهذا الاتجاه من الإرجاع.', $progress['item_title'])],
                ]);
            }

            if ($line['weight'] > ($limitWeight + 0.0001)) {
                throw ValidationException::withMessages([
                    'weight' => [sprintf('الوزن المحدد للصنف %s يتجاوز الحد المسموح لهذا الاتجاه من الإرجاع.', $progress['item_title'])],
                ]);
            }

            if ($direction === 'to_manufacturer' && $line['weight'] > ($availableWeight + 0.0001)) {
                throw ValidationException::withMessages([
                    'weight' => [sprintf('الرصيد الحالي في الفرع للصنف %s لا يكفي لإرجاع هذا الوزن إلى المصنع.', $progress['item_title'])],
                ]);
            }

            $stockAccountField = 'stock_account_' . ($parentDetail->goldCaratType?->key ?? 'crafted');
            $stockAccountId = $accountSetting->{$stockAccountField} ?? null;

            if (! $stockAccountId) {
                throw ValidationException::withMessages([
                    'bill_date' => [sprintf('لا يوجد حساب مخزون معرف للصنف %s في إعدادات الفرع.', $progress['item_title'])],
                ]);
            }

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
    private function journalLines(Invoice $return, Collection $lines, string $direction): array
    {
        $journalLines = [];
        $documentDate = $return->date;
        $stockGroups = $lines->groupBy('stock_account_id');

        if ($direction === 'from_manufacturer') {
            foreach ($stockGroups as $stockAccountId => $accountLines) {
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
                    'account_id' => $return->account_id,
                    'debit' => 0,
                    'credit' => $creditTotal,
                    'document_date' => $documentDate,
                ];
            }

            return $journalLines;
        }

        $debitTotal = round((float) $lines->sum('net_total'), 2);
        if ($debitTotal > 0) {
            $journalLines[] = [
                'account_id' => $return->account_id,
                'debit' => $debitTotal,
                'credit' => 0,
                'document_date' => $documentDate,
            ];
        }

        foreach ($stockGroups as $stockAccountId => $accountLines) {
            $creditTotal = round((float) $accountLines->sum('net_total'), 2);

            if ($creditTotal <= 0) {
                continue;
            }

            $journalLines[] = [
                'account_id' => (int) $stockAccountId,
                'debit' => 0,
                'credit' => $creditTotal,
                'document_date' => $documentDate,
            ];
        }

        return $journalLines;
    }
}
