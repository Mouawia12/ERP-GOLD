<?php

namespace App\Services\Dashboard;

use App\Models\Branch;
use App\Models\BranchItem;
use App\Models\Customer;
use App\Models\GoldPrice;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Item;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OwnerDashboardService
{
    private const DASHBOARD_INVOICE_TYPES = ['sale', 'sale_return', 'purchase', 'purchase_return'];

    public function buildForUser(User $user): array
    {
        $branchId = $user->is_admin ? null : $user->branch_id;
        $today = $this->businessDate($branchId);
        $scopeBranch = $branchId ? Branch::query()->find($branchId) : null;

        $overview = [
            'today_sales_total' => $this->sumInvoices($today, $branchId, 'sale'),
            'today_sales_return_total' => $this->sumInvoices($today, $branchId, 'sale_return'),
            'today_purchases_total' => $this->sumInvoices($today, $branchId, 'purchase'),
            'today_purchase_return_total' => $this->sumInvoices($today, $branchId, 'purchase_return'),
            'today_sold_weight' => $this->sumWeightedGold($today, $branchId, 'sale', 'out_weight'),
            'today_purchased_weight' => $this->sumWeightedGold($today, $branchId, 'purchase', 'in_weight'),
            'today_invoice_count' => $this->invoiceBaseQuery($today, $branchId)
                ->whereIn('type', self::DASHBOARD_INVOICE_TYPES)
                ->count(),
            'today_active_branches_count' => $this->invoiceBaseQuery($today, $branchId)
                ->whereNotNull('branch_id')
                ->distinct('branch_id')
                ->count('branch_id'),
        ];

        $overview['today_net_sales_total'] = round(
            $overview['today_sales_total'] - $overview['today_sales_return_total'],
            2
        );

        return [
            'today' => $today,
            'scopeBranch' => $scopeBranch,
            'scopeLabel' => $scopeBranch?->branch_name ?? 'جميع الفروع',
            'latestGoldPrice' => GoldPrice::latestSnapshot(),
            'overview' => $overview,
            'directoryCounts' => [
                'branches' => $branchId ? 1 : Branch::query()->count(),
                'users' => User::query()
                    ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
                    ->count(),
                'items' => $branchId
                    ? BranchItem::query()->where('branch_id', $branchId)->where('is_active', true)->distinct('item_id')->count('item_id')
                    : Item::query()->count(),
                'customers' => Customer::query()->where('type', 'customer')->count(),
                'suppliers' => Customer::query()->where('type', 'supplier')->count(),
            ],
            'purchaseBreakdown' => $this->purchaseBreakdown($today, $branchId),
            'topUsers' => $this->topUsers($today, $branchId),
            'topBranches' => $this->topBranches($today, $branchId),
        ];
    }

    private function sumInvoices(Carbon $today, ?int $branchId, string $type): float
    {
        return round((float) $this->invoiceBaseQuery($today, $branchId)
            ->where('type', $type)
            ->sum('net_total'), 2);
    }

    private function sumWeightedGold(Carbon $today, ?int $branchId, string $type, string $weightColumn): float
    {
        $value = InvoiceDetail::query()
            ->join('invoices', 'invoice_details.invoice_id', '=', 'invoices.id')
            ->leftJoin('gold_carats', 'invoice_details.gold_carat_id', '=', 'gold_carats.id')
            ->where('invoices.type', $type)
            ->whereDate('invoices.date', $today->format('Y-m-d'))
            ->when($branchId, fn (Builder $query) => $query->where('invoices.branch_id', $branchId))
            ->sum(DB::raw("invoice_details.{$weightColumn} * COALESCE(NULLIF(gold_carats.transform_factor, ''), 1)"));

        return round((float) $value, 3);
    }

    private function purchaseBreakdown(Carbon $today, ?int $branchId): Collection
    {
        return InvoiceDetail::query()
            ->join('invoices', 'invoice_details.invoice_id', '=', 'invoices.id')
            ->leftJoin('gold_carats', 'invoice_details.gold_carat_id', '=', 'gold_carats.id')
            ->where('invoices.type', 'purchase')
            ->whereDate('invoices.date', $today->format('Y-m-d'))
            ->when($branchId, fn (Builder $query) => $query->where('invoices.branch_id', $branchId))
            ->selectRaw('
                invoice_details.gold_carat_id as gold_carat_id,
                gold_carats.title as carat_title_raw,
                COUNT(DISTINCT invoices.id) as invoice_count,
                SUM(invoice_details.in_weight) as total_in_weight,
                SUM(invoice_details.net_total) as total_net_total
            ')
            ->groupBy('invoice_details.gold_carat_id', 'gold_carats.title')
            ->orderByDesc('total_in_weight')
            ->get()
            ->map(function ($row) {
                $row->carat_title = $this->translatedTitle($row->carat_title_raw);
                $row->total_in_weight = round((float) $row->total_in_weight, 3);
                $row->total_net_total = round((float) $row->total_net_total, 2);

                return $row;
            });
    }

    private function topUsers(Carbon $today, ?int $branchId): Collection
    {
        return Invoice::query()
            ->join('users', 'invoices.user_id', '=', 'users.id')
            ->leftJoin('branches', 'invoices.branch_id', '=', 'branches.id')
            ->whereDate('invoices.date', $today->format('Y-m-d'))
            ->whereIn('invoices.type', self::DASHBOARD_INVOICE_TYPES)
            ->when($branchId, fn (Builder $query) => $query->where('invoices.branch_id', $branchId))
            ->selectRaw('
                users.id as user_id,
                users.name as user_name,
                branches.name as branch_name_raw,
                COUNT(invoices.id) as invoice_count,
                SUM(CASE WHEN invoices.type = "sale" THEN invoices.net_total ELSE 0 END) as sales_total,
                SUM(CASE WHEN invoices.type = "sale_return" THEN invoices.net_total ELSE 0 END) as sales_return_total,
                SUM(CASE WHEN invoices.type = "purchase" THEN invoices.net_total ELSE 0 END) as purchases_total,
                SUM(CASE WHEN invoices.type = "purchase_return" THEN invoices.net_total ELSE 0 END) as purchase_return_total,
                SUM(invoices.net_total) as processed_total
            ')
            ->groupBy('users.id', 'users.name', 'branches.name')
            ->orderByDesc('invoice_count')
            ->orderByDesc('processed_total')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                $row->branch_name = $this->translatedTitle($row->branch_name_raw, 'بدون فرع');
                $row->sales_total = round((float) $row->sales_total, 2);
                $row->sales_return_total = round((float) $row->sales_return_total, 2);
                $row->purchases_total = round((float) $row->purchases_total, 2);
                $row->purchase_return_total = round((float) $row->purchase_return_total, 2);
                $row->processed_total = round((float) $row->processed_total, 2);

                return $row;
            });
    }

    private function topBranches(Carbon $today, ?int $branchId): Collection
    {
        return Invoice::query()
            ->join('branches', 'invoices.branch_id', '=', 'branches.id')
            ->whereDate('invoices.date', $today->format('Y-m-d'))
            ->whereIn('invoices.type', self::DASHBOARD_INVOICE_TYPES)
            ->when($branchId, fn (Builder $query) => $query->where('invoices.branch_id', $branchId))
            ->selectRaw('
                branches.id as branch_id,
                branches.name as branch_name_raw,
                COUNT(invoices.id) as invoice_count,
                SUM(CASE WHEN invoices.type = "sale" THEN invoices.net_total ELSE 0 END) as sales_total,
                SUM(CASE WHEN invoices.type = "sale_return" THEN invoices.net_total ELSE 0 END) as sales_return_total,
                SUM(CASE WHEN invoices.type = "purchase" THEN invoices.net_total ELSE 0 END) as purchases_total,
                SUM(CASE WHEN invoices.type = "purchase_return" THEN invoices.net_total ELSE 0 END) as purchase_return_total
            ')
            ->groupBy('branches.id', 'branches.name')
            ->orderByRaw('(SUM(CASE WHEN invoices.type = "sale" THEN invoices.net_total ELSE 0 END) - SUM(CASE WHEN invoices.type = "sale_return" THEN invoices.net_total ELSE 0 END)) DESC')
            ->orderByDesc('invoice_count')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                $row->branch_name = $this->translatedTitle($row->branch_name_raw, 'بدون فرع');
                $row->sales_total = round((float) $row->sales_total, 2);
                $row->sales_return_total = round((float) $row->sales_return_total, 2);
                $row->purchases_total = round((float) $row->purchases_total, 2);
                $row->purchase_return_total = round((float) $row->purchase_return_total, 2);
                $row->net_sales_total = round($row->sales_total - $row->sales_return_total, 2);

                return $row;
            });
    }

    private function invoiceBaseQuery(Carbon $today, ?int $branchId): Builder
    {
        return Invoice::query()
            ->whereDate('date', $today->format('Y-m-d'))
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId));
    }

    private function businessDate(?int $branchId): Carbon
    {
        $latestDate = Invoice::query()
            ->whereIn('type', self::DASHBOARD_INVOICE_TYPES)
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->max('date');

        if ($latestDate) {
            return Carbon::parse($latestDate);
        }

        return Carbon::today();
    }

    private function translatedTitle(?string $value, string $fallback = 'بدون عيار'): string
    {
        if (blank($value)) {
            return $fallback;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded[app()->getLocale()] ?? $decoded['ar'] ?? $decoded['en'] ?? reset($decoded) ?: $fallback;
        }

        return $value;
    }
}
