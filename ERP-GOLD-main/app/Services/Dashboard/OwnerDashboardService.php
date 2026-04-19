<?php

namespace App\Services\Dashboard;

use App\Models\Branch;
use App\Models\BranchItem;
use App\Models\Customer;
use App\Models\GoldPrice;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Item;
use App\Models\Subscriber;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OwnerDashboardService
{
    private const DASHBOARD_INVOICE_TYPES = ['sale', 'sale_return', 'purchase', 'purchase_return'];

    /**
     * @param  array<int>|null  $branchIds
     */
    public function buildForUser(User $user, ?array $branchIds = null, ?string $scopeLabelOverride = null, ?string $scopeModeLabelOverride = null): array
    {
        $scopedBranchIds = $this->resolveScopedBranchIds($user, $branchIds);
        $singleBranchId = $scopedBranchIds !== null && count($scopedBranchIds) === 1
            ? $scopedBranchIds[0]
            : null;
        $today = $this->businessDate($scopedBranchIds);
        $scopeBranch = $singleBranchId ? Branch::query()->find($singleBranchId) : null;

        $overview = [
            'today_sales_total' => $this->sumInvoices($today, $scopedBranchIds, 'sale'),
            'today_sales_return_total' => $this->sumInvoices($today, $scopedBranchIds, 'sale_return'),
            'today_purchases_total' => $this->sumInvoices($today, $scopedBranchIds, 'purchase'),
            'today_purchase_return_total' => $this->sumInvoices($today, $scopedBranchIds, 'purchase_return'),
            'today_sold_weight' => $this->sumWeightedGold($today, $scopedBranchIds, 'sale', 'out_weight'),
            'today_purchased_weight' => $this->sumWeightedGold($today, $scopedBranchIds, 'purchase', 'in_weight'),
            'today_invoice_count' => $this->invoiceBaseQuery($today, $scopedBranchIds)
                ->whereIn('type', self::DASHBOARD_INVOICE_TYPES)
                ->count(),
            'today_active_branches_count' => $this->invoiceBaseQuery($today, $scopedBranchIds)
                ->whereNotNull('branch_id')
                ->distinct('branch_id')
                ->count('branch_id'),
            'active_subscribers_count' => Subscriber::query()
                ->where('status', true)
                ->when(
                    $scopedBranchIds === null,
                    fn ($query) => $query,
                    fn ($query) => $query->whereHas('branches', fn ($branchQuery) => $branchQuery->whereIn('id', $scopedBranchIds))
                )
                ->count(),
        ];

        $overview['today_net_sales_total'] = round(
            $overview['today_sales_total'] - $overview['today_sales_return_total'],
            2
        );

        return [
            'today' => $today,
            'scopeBranch' => $scopeBranch,
            'scopeLabel' => $scopeLabelOverride ?? $scopeBranch?->branch_name ?? 'جميع الفروع',
            'scopeModeLabel' => $scopeModeLabelOverride ?? ($scopeBranch ? 'عرض الفرع النشط فقط' : 'عرض جميع الفروع'),
            'latestGoldPrice' => GoldPrice::latestSnapshot(),
            'overview' => $overview,
            'directoryCounts' => [
                'subscribers' => $singleBranchId
                    ? Branch::query()->whereKey($singleBranchId)->whereNotNull('subscriber_id')->distinct('subscriber_id')->count('subscriber_id')
                    : ($scopedBranchIds !== null
                        ? Branch::query()->whereIn('id', $scopedBranchIds)->whereNotNull('subscriber_id')->distinct('subscriber_id')->count('subscriber_id')
                        : Subscriber::query()->count()),
                'branches' => $singleBranchId ? 1 : ($scopedBranchIds !== null ? count($scopedBranchIds) : Branch::query()->count()),
                'users' => User::query()
                    ->when($scopedBranchIds !== null, fn (Builder $query) => $query->whereIn('branch_id', $scopedBranchIds))
                    ->when($scopedBranchIds === null, fn (Builder $query) => $query->whereNotNull('subscriber_id'))
                    ->count(),
                'items' => $singleBranchId
                    ? BranchItem::query()->where('branch_id', $singleBranchId)->where('is_active', true)->distinct('item_id')->count('item_id')
                    : ($scopedBranchIds !== null
                        ? BranchItem::query()->whereIn('branch_id', $scopedBranchIds)->where('is_active', true)->distinct('item_id')->count('item_id')
                        : Item::query()->count()),
                'customers' => Customer::query()->where('type', 'customer')->count(),
                'suppliers' => Customer::query()->where('type', 'supplier')->count(),
            ],
            'purchaseBreakdown' => $this->purchaseBreakdown($today, $scopedBranchIds),
            'topUsers' => $this->topUsers($today, $scopedBranchIds),
            'topBranches' => $this->topBranches($today, $scopedBranchIds),
        ];
    }

    /**
     * @param  array<int>|null  $branchIds
     * @return array<int>|null
     */
    private function resolveScopedBranchIds(User $user, ?array $branchIds): ?array
    {
        if ($user->isOwner()) {
            return null;
        }

        if ($branchIds === null) {
            return filled($user->branch_id) ? [(int) $user->branch_id] : [];
        }

        return collect($branchIds)
            ->map(fn ($branchId) => (int) $branchId)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int>|null  $branchIds
     */
    private function sumInvoices(Carbon $today, ?array $branchIds, string $type): float
    {
        return round((float) $this->invoiceBaseQuery($today, $branchIds)
            ->where('type', $type)
            ->sum('net_total'), 2);
    }

    /**
     * @param  array<int>|null  $branchIds
     */
    private function sumWeightedGold(Carbon $today, ?array $branchIds, string $type, string $weightColumn): float
    {
        $value = InvoiceDetail::query()
            ->join('invoices', 'invoice_details.invoice_id', '=', 'invoices.id')
            ->leftJoin('gold_carats', 'invoice_details.gold_carat_id', '=', 'gold_carats.id')
            ->where('invoices.type', $type)
            ->whereDate('invoices.date', $today->format('Y-m-d'))
            ->when($branchIds !== null, fn (Builder $query) => $query->whereIn('invoices.branch_id', $branchIds))
            ->sum(DB::raw("invoice_details.{$weightColumn} * COALESCE(NULLIF(gold_carats.transform_factor, ''), 1)"));

        return round((float) $value, 3);
    }

    /**
     * @param  array<int>|null  $branchIds
     */
    private function purchaseBreakdown(Carbon $today, ?array $branchIds): Collection
    {
        return InvoiceDetail::query()
            ->join('invoices', 'invoice_details.invoice_id', '=', 'invoices.id')
            ->leftJoin('gold_carats', 'invoice_details.gold_carat_id', '=', 'gold_carats.id')
            ->where('invoices.type', 'purchase')
            ->whereDate('invoices.date', $today->format('Y-m-d'))
            ->when($branchIds !== null, fn (Builder $query) => $query->whereIn('invoices.branch_id', $branchIds))
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

    /**
     * @param  array<int>|null  $branchIds
     */
    private function topUsers(Carbon $today, ?array $branchIds): Collection
    {
        return Invoice::query()
            ->join('users', 'invoices.user_id', '=', 'users.id')
            ->leftJoin('branches', 'invoices.branch_id', '=', 'branches.id')
            ->whereDate('invoices.date', $today->format('Y-m-d'))
            ->whereIn('invoices.type', self::DASHBOARD_INVOICE_TYPES)
            ->when($branchIds !== null, fn (Builder $query) => $query->whereIn('invoices.branch_id', $branchIds))
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

    /**
     * @param  array<int>|null  $branchIds
     */
    private function topBranches(Carbon $today, ?array $branchIds): Collection
    {
        return Invoice::query()
            ->join('branches', 'invoices.branch_id', '=', 'branches.id')
            ->whereDate('invoices.date', $today->format('Y-m-d'))
            ->whereIn('invoices.type', self::DASHBOARD_INVOICE_TYPES)
            ->when($branchIds !== null, fn (Builder $query) => $query->whereIn('invoices.branch_id', $branchIds))
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

    /**
     * @param  array<int>|null  $branchIds
     */
    private function invoiceBaseQuery(Carbon $today, ?array $branchIds): Builder
    {
        return Invoice::query()
            ->whereDate('date', $today->format('Y-m-d'))
            ->when($branchIds !== null, fn (Builder $query) => $query->whereIn('branch_id', $branchIds));
    }

    /**
     * @param  array<int>|null  $branchIds
     */
    private function businessDate(?array $branchIds): Carbon
    {
        $latestDate = Invoice::query()
            ->whereIn('type', self::DASHBOARD_INVOICE_TYPES)
            ->when($branchIds !== null, fn (Builder $query) => $query->whereIn('branch_id', $branchIds))
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
