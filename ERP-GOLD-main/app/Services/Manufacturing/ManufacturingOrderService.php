<?php

namespace App\Services\Manufacturing;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\User;
use App\Services\JournalEntriesService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManufacturingOrderService
{
    public function goldItemsForBranch(Branch $branch): Collection
    {
        return Item::query()
            ->with(['goldCarat', 'goldCaratType'])
            ->publishedToBranch($branch->id, false)
            ->where('inventory_classification', Item::CLASSIFICATION_GOLD)
            ->orderBy('id')
            ->get()
            ->map(function (Item $item) use ($branch) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'gold_carat_label' => $item->goldCarat?->title ?? '-',
                    'gold_carat_type_label' => $item->goldCaratType?->title ?? '-',
                    'available_weight' => $this->availableWeightForBranch($item, $branch->id),
                    'available_quantity' => $this->availableQuantityForBranch($item, $branch->id),
                ];
            })
            ->values();
    }

    public function availableWeightForBranch(Item $item, int $branchId): float
    {
        return round((float) DB::table('invoice_details')
            ->join('invoices', 'invoices.id', '=', 'invoice_details.invoice_id')
            ->where('invoices.branch_id', $branchId)
            ->where('invoice_details.item_id', $item->id)
            ->sum(DB::raw('invoice_details.in_weight - invoice_details.out_weight')), 3);
    }

    public function availableQuantityForBranch(Item $item, int $branchId): float
    {
        return round((float) DB::table('invoice_details')
            ->join('invoices', 'invoices.id', '=', 'invoice_details.invoice_id')
            ->where('invoices.branch_id', $branchId)
            ->where('invoice_details.item_id', $item->id)
            ->sum(DB::raw('invoice_details.in_quantity - invoice_details.out_quantity')), 3);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload, User $user): Invoice
    {
        $branch = Branch::query()->with('accountSetting', 'warehouses')->findOrFail((int) $payload['branch_id']);
        $manufacturer = Customer::query()
            ->where('type', 'supplier')
            ->find($payload['manufacturer_id']);

        if (! $manufacturer) {
            throw ValidationException::withMessages([
                'manufacturer_id' => ['المصنع الخارجي المحدد غير صالح أو ليس موردًا.'],
            ]);
        }

        $financialYearId = FinancialYear::query()->where('is_active', true)->value('id');
        if (! $financialYearId) {
            throw ValidationException::withMessages([
                'bill_date' => ['لا توجد سنة مالية نشطة يمكن ربط أمر التصنيع بها.'],
            ]);
        }

        $lines = $this->normalizedLines($payload, $branch);
        $orderDate = Carbon::parse((string) $payload['bill_date']);
        $warehouseId = $branch->warehouses->first()?->id;

        return DB::transaction(function () use ($payload, $user, $branch, $manufacturer, $financialYearId, $lines, $orderDate, $warehouseId) {
            $invoice = Invoice::create([
                'branch_id' => $branch->id,
                'warehouse_id' => $warehouseId,
                'customer_id' => $manufacturer->id,
                'bill_client_name' => $manufacturer->name,
                'bill_client_phone' => $manufacturer->phone,
                'bill_client_identity_number' => $manufacturer->identity_number ?? null,
                'financial_year' => $financialYearId,
                'type' => 'manufacturing_order',
                'account_id' => $payload['account_id'],
                'notes' => $payload['notes'] ?? '',
                'date' => $orderDate->format('Y-m-d'),
                'time' => $orderDate->format('H:i:s'),
                'lines_total' => round($lines->sum('net_total'), 2),
                'discount_total' => 0,
                'lines_total_after_discount' => round($lines->sum('net_total'), 2),
                'taxes_total' => 0,
                'net_total' => round($lines->sum('net_total'), 2),
                'user_id' => $user->id,
            ]);

            $invoice->details()->createMany($lines->map(function (array $line) use ($warehouseId, $orderDate) {
                return [
                    'warehouse_id' => $warehouseId,
                    'item_id' => $line['item']->id,
                    'gold_carat_id' => $line['item']->gold_carat_id,
                    'gold_carat_type_id' => $line['item']->gold_carat_type_id,
                    'date' => $orderDate->format('Y-m-d'),
                    'in_quantity' => 0,
                    'out_quantity' => $line['quantity'],
                    'in_weight' => 0,
                    'out_weight' => $line['weight'],
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

            JournalEntriesService::invoiceGenerateJournalEntries($invoice, $this->journalLines($invoice, $lines));

            return $invoice->load([
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
    private function normalizedLines(array $payload, Branch $branch): Collection
    {
        $lineInputs = collect($payload['item_id'] ?? [])
            ->map(function ($itemId, $index) use ($payload) {
                return [
                    'item_id' => $itemId,
                    'quantity' => (float) ($payload['quantity'][$index] ?? 0),
                    'weight' => (float) ($payload['weight'][$index] ?? 0),
                ];
            })
            ->filter(fn (array $line) => filled($line['item_id']) && $line['quantity'] > 0 && $line['weight'] > 0)
            ->values();

        if ($lineInputs->isEmpty()) {
            throw ValidationException::withMessages([
                'item_id' => ['يجب إدخال سطر تصنيع واحد على الأقل بوزن صحيح.'],
            ]);
        }

        $accountSetting = $branch->accountSetting;

        if (! $accountSetting) {
            throw ValidationException::withMessages([
                'branch_id' => ['لا توجد إعدادات حسابات مرتبطة بهذا الفرع.'],
            ]);
        }

        $requestedByItem = $lineInputs->groupBy('item_id')->map(fn (Collection $rows) => round((float) $rows->sum('weight'), 3));

        return $lineInputs->map(function (array $line) use ($branch, $requestedByItem, $accountSetting) {
            $item = Item::query()
                ->with(['defaultUnit', 'goldCaratType'])
                ->publishedToBranch($branch->id, false)
                ->where('inventory_classification', Item::CLASSIFICATION_GOLD)
                ->find($line['item_id']);

            if (! $item) {
                throw ValidationException::withMessages([
                    'item_id' => ['أحد الأصناف المحددة غير صالح لهذا الفرع أو غير ذهبي.'],
                ]);
            }

            $availableWeight = $this->availableWeightForBranch($item, $branch->id);
            $requestedWeight = (float) ($requestedByItem[$item->id] ?? $line['weight']);

            if ($requestedWeight > $availableWeight) {
                throw ValidationException::withMessages([
                    'weight' => [sprintf('الوزن المطلوب للصنف %s يتجاوز الرصيد المتاح في الفرع.', $item->title)],
                ]);
            }

            $stockAccountField = 'stock_account_' . ($item->goldCaratType?->key ?? 'crafted');
            $stockAccountId = $accountSetting->{$stockAccountField} ?? null;

            if (! $stockAccountId) {
                throw ValidationException::withMessages([
                    'account_id' => [sprintf('لا يوجد حساب مخزون معرف لعيار/نوع الصنف %s في إعدادات الفرع.', $item->title)],
                ]);
            }

            $unitCost = round((float) ($item->defaultUnit?->average_cost_per_gram ?? 0), 4);
            $netTotal = round($line['weight'] * $unitCost, 2);

            return [
                'item' => $item,
                'quantity' => round($line['quantity'], 3),
                'weight' => round($line['weight'], 3),
                'unit_cost' => $unitCost,
                'net_total' => $netTotal,
                'available_weight' => $availableWeight,
                'stock_account_id' => (int) $stockAccountId,
            ];
        })->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $lines
     * @return array<int, array<string, mixed>>
     */
    private function journalLines(Invoice $invoice, Collection $lines): array
    {
        $journalLines = [];
        $documentDate = $invoice->date;
        $totalValue = round((float) $lines->sum('net_total'), 2);

        if ($totalValue > 0) {
            $journalLines[] = [
                'account_id' => $invoice->account_id,
                'debit' => $totalValue,
                'credit' => 0,
                'document_date' => $documentDate,
            ];
        }

        foreach ($lines->groupBy('stock_account_id') as $stockAccountId => $accountLines) {
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
