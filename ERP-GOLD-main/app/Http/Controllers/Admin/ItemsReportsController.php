<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\GoldCarat;
use App\Models\InvoiceDetail;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\User;
use App\Services\Branches\BranchContextService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use DB;

class ItemsReportsController extends Controller
{
    public function item_list_report()
    {
        return view('admin.reports.items.search', $this->itemListFiltersData());
    }

    public function item_list_report_search(Request $request)
    {
        $subscriberId = $this->currentSubscriberId();
        $filters = [
            'branch_id' => $this->normalizeOptionalFilter($request->input('branch_id')),
            'inventory_classification' => $this->normalizeOptionalFilter($request->input('inventory_classification')),
            'carat' => $this->normalizeOptionalFilter($request->input('carat')),
            'category' => $this->normalizeOptionalFilter($request->input('category')),
            'code' => $this->normalizeOptionalFilter($request->input('code')),
            'name' => $this->normalizeOptionalFilter($request->input('name')),
            'from_code' => $this->normalizeOptionalFilter($request->input('fcode')),
            'to_code' => $this->normalizeOptionalFilter($request->input('tcode')),
            'status' => $request->filled('status') ? (string) $request->input('status') : null,
        ];

        $items = Item::query()
            ->with(['branch', 'category', 'goldCarat', 'publishedBranches'])
            ->when($subscriberId, function ($query, $value) {
                return $query->whereHas('branch', fn ($branchQuery) => $branchQuery->where('subscriber_id', $value));
            })
            ->when($filters['branch_id'], function ($query, $value) {
                return $query->publishedToBranch((int) $value);
            })
            ->when($filters['inventory_classification'], function ($query, $value) {
                return $query->where('inventory_classification', $value);
            })
            ->when($filters['carat'], function ($query, $value) {
                return $query->where('gold_carat_id', $value);
            })
            ->when($filters['category'], function ($query, $value) {
                return $query->where('category_id', $value);
            })
            ->when($filters['code'], function ($query, $value) {
                return $query->where('code', $value);
            })
            ->when($filters['name'], function ($query, $value) {
                return $query->where('title', 'like', '%' . $value . '%');
            })
            ->when($filters['from_code'] || $filters['to_code'], function ($query) use ($filters) {
                if ($filters['from_code'] && $filters['to_code']) {
                    return $query->whereBetween('code', [$filters['from_code'], $filters['to_code']]);
                }

                if ($filters['from_code']) {
                    return $query->where('code', '>=', $filters['from_code']);
                }

                return $query->where('code', '<=', $filters['to_code']);
            })
            ->when($filters['status'] !== null, function ($query) use ($filters) {
                return $query->where('status', $filters['status'] === '1');
            })
            ->orderBy('code')
            ->get();

        $branch = $filters['branch_id'] ? $this->branchesQuery($subscriberId)->find($filters['branch_id']) : null;
        $inventoryClassifications = Item::inventoryClassificationOptions();

        return view('admin.reports.items.index', compact('items', 'branch', 'filters', 'inventoryClassifications'));
    }

    public function sold_items_report()
    {
        return view('admin.reports.sold_items.search', $this->soldItemsFiltersData());
    }

    public function sold_items_report_search(Request $request)
    {
        $subscriberId = $this->currentSubscriberId();
        [$periodFrom, $periodTo] = $this->resolvePeriod(
            $request,
            Carbon::now()->startOfYear()->format('Y-m-d'),
            Carbon::now()->endOfYear()->format('Y-m-d')
        );

        $filters = [
            'branch_id' => $this->normalizeOptionalFilter($request->input('branch_id')),
            'user_id' => $this->normalizeOptionalFilter($request->input('user_id')),
            'inventory_classification' => $this->normalizeOptionalFilter($request->input('inventory_classification')),
            'carat' => $this->normalizeOptionalFilter($request->input('carat')),
            'category' => $this->normalizeOptionalFilter($request->input('category')),
            'code' => $this->normalizeOptionalFilter($request->input('code')),
            'name' => $this->normalizeOptionalFilter($request->input('name')),
            'invoice_number' => $this->normalizeOptionalFilter($request->input('invoice_number', $request->input('billNumber'))),
            'from_time' => $this->normalizeTime($request->input('from_time')),
            'to_time' => $this->normalizeTime($request->input('to_time')),
        ];

        $itemsTransactions = InvoiceDetail::query()
            ->with(['invoice.branch', 'invoice.user', 'item', 'carat'])
            ->whereHas('invoice', function ($query) use ($filters, $periodFrom, $periodTo, $subscriberId) {
                $query->where('type', 'sale')
                    ->whereBetween('date', [$periodFrom, $periodTo])
                    ->when($subscriberId, function ($builder, $value) {
                        return $builder->whereHas('branch', fn ($branchQuery) => $branchQuery->where('subscriber_id', $value));
                    })
                    ->when($filters['branch_id'], function ($builder, $value) {
                        return $builder->where('branch_id', $value);
                    })
                    ->when($filters['user_id'], function ($builder, $value) {
                        return $builder->where('user_id', $value);
                    })
                    ->when($filters['invoice_number'], function ($builder, $value) {
                        return $builder->where('bill_number', $value);
                    })
                    ->when($filters['from_time'], function ($builder, $value) {
                        return $builder->where('time', '>=', $value);
                    })
                    ->when($filters['to_time'], function ($builder, $value) {
                        return $builder->where('time', '<=', $value);
                    });
            })
            ->when($filters['inventory_classification'] || $filters['category'] || $filters['code'] || $filters['name'], function ($query) use ($filters) {
                $query->whereHas('item', function ($builder) use ($filters) {
                    $builder
                        ->when($filters['inventory_classification'], function ($itemQuery, $value) {
                            return $itemQuery->where('inventory_classification', $value);
                        })
                        ->when($filters['category'], function ($itemQuery, $value) {
                            return $itemQuery->where('category_id', $value);
                        })
                        ->when($filters['code'], function ($itemQuery, $value) {
                            return $itemQuery->where('code', $value);
                        })
                        ->when($filters['name'], function ($itemQuery, $value) {
                            return $itemQuery->where('title', 'like', '%' . $value . '%');
                        });
                });
            })
            ->when($filters['carat'], function ($query, $value) {
                return $query->where('gold_carat_id', $value);
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get()
            ->sortByDesc(function (InvoiceDetail $detail) {
                return sprintf(
                    '%s %s %010d',
                    $detail->invoice?->date ?? '',
                    $detail->invoice?->time ?? '00:00:00',
                    $detail->id
                );
            })
            ->values();

        $branch = $filters['branch_id'] ? $this->branchesQuery($subscriberId)->find($filters['branch_id']) : null;
        $selectedUser = $filters['user_id'] ? $this->usersQuery($subscriberId)->find($filters['user_id']) : null;

        return view('admin.reports.sold_items.index', compact(
            'itemsTransactions',
            'periodFrom',
            'periodTo',
            'branch',
            'selectedUser',
            'filters'
        ));
    }

    private function soldItemsFiltersData(): array
    {
        $currentUser = auth('admin-web')->user();
        $subscriberId = $this->currentSubscriberId();

        return [
            'branches' => $this->branchesQuery($subscriberId)->orderBy('id')->get(),
            'users' => $this->usersQuery($subscriberId)->orderBy('name')->get(),
            'carats' => GoldCarat::query()->orderBy('id')->get(),
            'categories' => ItemCategory::query()->orderBy('id')->get(),
            'inventoryClassifications' => Item::inventoryClassificationOptions(),
            'defaultFilters' => [
                'date_from' => Carbon::now()->startOfYear()->format('Y-m-d'),
                'date_to' => Carbon::now()->endOfYear()->format('Y-m-d'),
                'from_time' => '',
                'to_time' => '',
                'invoice_number' => '',
                'user_id' => '',
                'branch_id' => $currentUser?->is_admin ? '' : $currentUser?->branch_id,
                'inventory_classification' => '',
                'carat' => '',
                'category' => '',
                'code' => '',
                'name' => '',
            ],
        ];
    }

    private function itemListFiltersData(): array
    {
        $currentUser = auth('admin-web')->user();
        $subscriberId = $this->currentSubscriberId();

        return [
            'branches' => $this->branchesQuery($subscriberId)->orderBy('id')->get(),
            'carats' => GoldCarat::query()->orderBy('id')->get(),
            'categories' => ItemCategory::query()->orderBy('id')->get(),
            'inventoryClassifications' => Item::inventoryClassificationOptions(),
            'defaultFilters' => [
                'branch_id' => $currentUser?->is_admin ? '' : $currentUser?->branch_id,
                'inventory_classification' => '',
                'carat' => '',
                'category' => '',
                'code' => '',
                'name' => '',
                'fcode' => '',
                'tcode' => '',
                'status' => '',
            ],
        ];
    }

    /**
     * @return array{0:string,1:string}
     */
    private function resolvePeriod(Request $request, string $defaultDateFrom, string $defaultDateTo): array
    {
        $periodFrom = $request->filled('date_from')
            ? Carbon::parse($request->input('date_from'))->format('Y-m-d')
            : $defaultDateFrom;

        $periodTo = $request->filled('date_to')
            ? Carbon::parse($request->input('date_to'))->format('Y-m-d')
            : $defaultDateTo;

        return [$periodFrom, $periodTo];
    }

    private function normalizeOptionalFilter($value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        return $value === '' || $value === '0' || $value === 0 ? null : $value;
    }

    private function normalizeTime(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return strlen($value) === 5 ? $value . ':00' : $value;
    }

    private function currentSubscriberId(): ?int
    {
        $user = auth('admin-web')->user();

        if (! $user || blank($user->subscriber_id)) {
            return null;
        }

        return (int) $user->subscriber_id;
    }

    private function branchesQuery(?int $subscriberId)
    {
        $user = auth('admin-web')->user();
        $accessibleBranchIds = $user
            ? app(BranchContextService::class)->accessibleBranchIds($user)
            : [];

        return Branch::query()
            ->where('status', 1)
            ->when($subscriberId, fn ($query) => $query->where('subscriber_id', $subscriberId))
            ->when($accessibleBranchIds !== [], fn ($query) => $query->whereIn('id', $accessibleBranchIds));
    }

    private function usersQuery(?int $subscriberId)
    {
        return User::query()
            ->when($subscriberId, fn ($query) => $query->where('subscriber_id', $subscriberId));
    }
}
