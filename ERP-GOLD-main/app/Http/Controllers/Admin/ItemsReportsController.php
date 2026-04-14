<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\GoldCarat;
use App\Models\InvoiceDetail;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Subscriber;
use App\Models\User;
use App\Services\Reports\ReportBranchSelectionService;
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
        $branchSelection = $this->branchSelection($request, $subscriberId);
        $filters = [
            'branch_ids' => $branchSelection['effective_branch_ids'],
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
            ->when($filters['branch_ids'] !== [], function ($query) use ($filters) {
                return $query->whereHas('branchPublications', function ($publicationQuery) use ($filters) {
                    $publicationQuery
                        ->whereIn('branch_id', $filters['branch_ids'])
                        ->where('is_active', true)
                        ->where('is_visible', true);
                });
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

        $branch = $branchSelection['single_branch'];
        $branchLabel = $branchSelection['branch_label'];
        $inventoryClassifications = Item::inventoryClassificationOptions();

        return view('admin.reports.items.index', compact('items', 'branch', 'branchLabel', 'filters', 'inventoryClassifications'));
    }

    public function collectible_item_list_report()
    {
        $data = $this->itemListFiltersData();
        $data['presetClassification'] = Item::CLASSIFICATION_COLLECTIBLE;
        $data['pageTitle'] = 'قائمة أصناف المقتنيات';
        $data['formAction'] = route('reports.collectible.items.list.search');
        return view('admin.reports.items.search', $data);
    }

    public function collectible_item_list_report_search(Request $request)
    {
        $request->merge(['inventory_classification' => Item::CLASSIFICATION_COLLECTIBLE]);
        return $this->item_list_report_search($request);
    }

    public function silver_item_list_report()
    {
        $data = $this->itemListFiltersData();
        $data['presetClassification'] = Item::CLASSIFICATION_SILVER;
        $data['pageTitle'] = 'قائمة أصناف الفضة';
        $data['formAction'] = route('reports.silver.items.list.search');
        return view('admin.reports.items.search', $data);
    }

    public function silver_item_list_report_search(Request $request)
    {
        $request->merge(['inventory_classification' => Item::CLASSIFICATION_SILVER]);
        return $this->item_list_report_search($request);
    }

    public function sold_items_report()
    {
        return view('admin.reports.sold_items.search', $this->soldItemsFiltersData());
    }

    public function sold_items_report_search(Request $request)
    {
        $subscriberId = $this->currentSubscriberId();
        $branchSelection = $this->branchSelection($request, $subscriberId);
        [$periodFrom, $periodTo] = $this->resolvePeriod(
            $request,
            Carbon::now()->startOfYear()->format('Y-m-d'),
            Carbon::now()->endOfYear()->format('Y-m-d')
        );

        $filters = [
            'branch_ids' => $branchSelection['effective_branch_ids'],
            'user_id' => $this->resolvedReportUserId($request, $subscriberId, $branchSelection['visible_branch_ids']),
            'inventory_classification' => $this->normalizeOptionalFilter($request->input('inventory_classification')),
            'carat' => $this->normalizeOptionalFilter($request->input('carat')),
            'category' => $this->normalizeOptionalFilter($request->input('category')),
            'code' => $this->normalizeOptionalFilter($request->input('code')),
            'name' => $this->normalizeOptionalFilter($request->input('name')),
            'invoice_number_from' => $this->normalizeOptionalFilter($request->input('invoice_number_from', $request->input('FromBillNumber', $request->input('invoice_number', $request->input('billNumber'))))),
            'invoice_number_to' => $this->normalizeOptionalFilter($request->input('invoice_number_to', $request->input('ToBillNumber', $request->input('invoice_number', $request->input('billNumber'))))),
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
                    ->when($filters['branch_ids'] !== [], function ($builder) use ($filters) {
                        return $builder->whereIn('branch_id', $filters['branch_ids']);
                    })
                    ->when($filters['user_id'], function ($builder, $value) {
                        return $builder->where('user_id', $value);
                    })
                    ->when($filters['invoice_number_from'] || $filters['invoice_number_to'], function ($builder) use ($filters) {
                        if ($filters['invoice_number_from'] && $filters['invoice_number_to']) {
                            return $builder->whereBetween('bill_number', [$filters['invoice_number_from'], $filters['invoice_number_to']]);
                        }

                        if ($filters['invoice_number_from']) {
                            return $builder->where('bill_number', '>=', $filters['invoice_number_from']);
                        }

                        return $builder->where('bill_number', '<=', $filters['invoice_number_to']);
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

        $branch = $branchSelection['single_branch'];
        $branchLabel = $branchSelection['branch_label'];
        $selectedUser = $filters['user_id'] ? $this->usersQuery($subscriberId, $branchSelection['visible_branch_ids'])->find($filters['user_id']) : null;

        return view('admin.reports.sold_items.index', compact(
            'itemsTransactions',
            'periodFrom',
            'periodTo',
            'branch',
            'branchLabel',
            'selectedUser',
            'filters'
        ));
    }

    public function collectible_sold_items_report()
    {
        $data = $this->soldItemsFiltersData();
        $data['presetClassification'] = Item::CLASSIFICATION_COLLECTIBLE;
        $data['pageTitle'] = 'تقرير الأصناف المباعة - المقتنيات';
        $data['formAction'] = route('reports.collectible.sold_items_report.search');
        return view('admin.reports.sold_items.search', $data);
    }

    public function collectible_sold_items_report_search(Request $request)
    {
        $request->merge(['inventory_classification' => Item::CLASSIFICATION_COLLECTIBLE]);
        return $this->sold_items_report_search($request);
    }

    public function silver_sold_items_report()
    {
        $data = $this->soldItemsFiltersData();
        $data['presetClassification'] = Item::CLASSIFICATION_SILVER;
        $data['pageTitle'] = 'تقرير الأصناف المباعة - الفضة';
        $data['formAction'] = route('reports.silver.sold_items_report.search');
        return view('admin.reports.sold_items.search', $data);
    }

    public function silver_sold_items_report_search(Request $request)
    {
        $request->merge(['inventory_classification' => Item::CLASSIFICATION_SILVER]);
        return $this->sold_items_report_search($request);
    }

    private function soldItemsFiltersData(): array
    {
        $subscriberId = $this->currentSubscriberId();
        $branchSelection = $this->availableBranchSelection($subscriberId);
        $userFilterOptions = $this->soldItemsUserFilterOptions($subscriberId, $branchSelection['visible_branch_ids']);

        return [
            'branches' => $branchSelection['branches'],
            'users' => $userFilterOptions['users'],
            'userFilterLocked' => $userFilterOptions['locked'],
            'carats' => GoldCarat::query()->orderBy('id')->get(),
            'categories' => ItemCategory::query()->orderBy('id')->get(),
            'inventoryClassifications' => Item::inventoryClassificationOptions(),
            'defaultFilters' => [
                'date_from' => Carbon::now()->startOfYear()->format('Y-m-d'),
                'date_to' => Carbon::now()->endOfYear()->format('Y-m-d'),
                'from_time' => '',
                'to_time' => '',
                'invoice_number_from' => '',
                'invoice_number_to' => '',
                'user_id' => $userFilterOptions['selected_user_id'],
                'branch_id' => $branchSelection['legacy_branch_id'],
                'branch_ids' => $branchSelection['selected_branch_ids'],
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
        $subscriberId = $this->currentSubscriberId();
        $branchSelection = $this->availableBranchSelection($subscriberId);

        return [
            'branches' => $branchSelection['branches'],
            'carats' => GoldCarat::query()->orderBy('id')->get(),
            'categories' => ItemCategory::query()->orderBy('id')->get(),
            'inventoryClassifications' => Item::inventoryClassificationOptions(),
            'defaultFilters' => [
                'branch_id' => $branchSelection['legacy_branch_id'],
                'branch_ids' => $branchSelection['selected_branch_ids'],
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
        $visibleBranchIds = $this->availableBranchSelection($subscriberId)['visible_branch_ids'];

        return Branch::query()
            ->where('status', 1)
            ->when($subscriberId, fn ($query) => $query->where('subscriber_id', $subscriberId))
            ->when($visibleBranchIds !== [], fn ($query) => $query->whereIn('id', $visibleBranchIds));
    }

    private function usersQuery(?int $subscriberId, array $visibleBranchIds = [])
    {
        $user = auth('admin-web')->user();

        return User::query()
            ->when(
                $subscriberId && ! $this->isSubscriberPrimaryAccount($user),
                fn ($query) => $query->whereKey($user?->id)
            )
            ->when($subscriberId, fn ($query) => $query->where('subscriber_id', $subscriberId));
    }

    /**
     * @return array{users:\Illuminate\Support\Collection<int,User>,locked:bool,selected_user_id:string}
     */
    private function soldItemsUserFilterOptions(?int $subscriberId, array $visibleBranchIds = []): array
    {
        $user = auth('admin-web')->user();
        $locked = $subscriberId !== null && ! $this->isSubscriberPrimaryAccount($user);

        return [
            'users' => $this->usersQuery($subscriberId, $visibleBranchIds)->orderBy('name')->get(),
            'locked' => $locked,
            'selected_user_id' => $locked && $user ? (string) $user->id : '',
        ];
    }

    private function resolvedReportUserId(Request $request, ?int $subscriberId, array $visibleBranchIds = []): ?int
    {
        $user = auth('admin-web')->user();

        if ($subscriberId !== null && ! $this->isSubscriberPrimaryAccount($user)) {
            return $user ? (int) $user->id : null;
        }

        $userId = $this->normalizeOptionalFilter($request->input('user_id'));

        if ($userId === null) {
            return null;
        }

        return $this->usersQuery($subscriberId, $visibleBranchIds)->whereKey((int) $userId)->exists()
            ? (int) $userId
            : null;
    }

    private function isSubscriberPrimaryAccount(?User $user): bool
    {
        if (! $user || blank($user->subscriber_id)) {
            return false;
        }

        $subscriber = $user->relationLoaded('subscriber')
            ? $user->subscriber
            : Subscriber::query()->select('id', 'admin_user_id')->find($user->subscriber_id);

        return (int) ($subscriber?->admin_user_id ?? 0) === (int) $user->id;
    }

    /**
     * @return array<string, mixed>
     */
    private function availableBranchSelection(?int $subscriberId): array
    {
        $request = Request::create('/', 'GET');

        return app(ReportBranchSelectionService::class)->resolve($request, auth('admin-web')->user(), $subscriberId);
    }

    /**
     * @return array<string, mixed>
     */
    private function branchSelection(Request $request, ?int $subscriberId): array
    {
        return app(ReportBranchSelectionService::class)->resolve($request, auth('admin-web')->user(), $subscriberId);
    }
}
