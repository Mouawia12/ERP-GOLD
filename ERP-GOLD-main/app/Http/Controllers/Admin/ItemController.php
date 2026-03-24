<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\GoldCarat;
use App\Models\GoldCaratType;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Services\Branches\BranchContextService;
use App\Services\Items\BarcodePrintProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;
use DataTables;

class ItemController extends Controller
{
    private const NON_GOLD_CARAT_TYPE = 'non_gold';

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $currentUser = $this->currentAdminUser();
        $data = Item::query()->with(['branch', 'category', 'goldCarat', 'goldCaratType', 'defaultUnit', 'publishedBranches']);

        if (!empty($currentUser?->branch_id)) {
            $data->publishedToBranch((int) $currentUser->branch_id);
        }

        if ($request->ajax()) {
            return Datatables::of($data)
                ->addIndexColumn()
                ->editColumn('title', function ($row) {
                    return $row->getTranslation('title', 'ar');
                })
                ->addColumn('status', function ($row) {
                    $row->status ? $span = 'متاح' : $span = 'غير متاح';
                    return $span;
                })
                ->addColumn('inventory_classification', function ($row) {
                    return $row->inventory_classification_label;
                })
                ->addColumn('category', function ($row) {
                    return $row->category->title ?? '-';
                })
                ->addColumn('gold_carat', function ($row) {
                    return $row->goldCarat->title ?? '-';
                })
                ->addColumn('gold_carat_type', function ($row) {
                    return $row->goldCaratType->title ?? '-';
                })
                ->addColumn('weight', function ($row) {
                    return $row->defaultUnit->weight ?? '-';
                })
                ->addColumn('published_branches', function ($row) {
                    $names = $row->publishedBranches->map(fn ($branch) => $branch->name)->filter()->values();
                    return $names->isNotEmpty() ? $names->implode('، ') : '-';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<a href=' . route('items.edit', $row->id) . ' class="btn btn-labeled btn-info 
                            value="' . $row->id . '" role="button"><i class="fa-regular fa-pen-to-square"></i>
                        </a>';

                    $btn = $btn . '<a href=' . route('items.barcode_table', $row->id) . ' class="btn btn-labeled btn-warning showBarcodeTable"
                            value="' . $row->id . '" role="button" target="_blank" ><i class="fa fa-barcode"></i>
                        </a>';

                    $btn = $btn . '<button type="button" class="btn btn-labeled btn-danger deleteBtn "
                            value="' . $row->id . '"><i class="fa fa-trash"></i>
                        </button>';

                    return $btn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        $categories = ItemCategory::all();
        $carats = GoldCarat::all();
        $branches = Branch::where('status', 1)->get();
        $inventoryClassifications = Item::inventoryClassificationOptions();

        return view('admin.items.index', compact('categories', 'carats', 'branches', 'inventoryClassifications'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $currentUser = $this->currentAdminUser();
        $categories = ItemCategory::all();
        $carats = GoldCarat::all();
        $caratTypes = GoldCaratType::all();
        $branches = $this->publicationBranchesForUser($currentUser);
        $inventoryClassifications = Item::inventoryClassificationOptions();
        $canManageBranchPublications = $branches->count() > 1;

        return view('admin.items.form', compact('categories', 'carats', 'caratTypes', 'branches', 'inventoryClassifications', 'canManageBranchPublications'));
    }

    public function barcodes_table($itemId, $returnType = 'json', ?string $paperProfileKey = null)
    {
        $item = Item::with(['branch', 'goldCarat', 'units'])->findOrFail($itemId);
        $barcodePrintProfileService = app(BarcodePrintProfileService::class);
        $paperProfiles = $barcodePrintProfileService->all();
        $defaultPaperProfile = $barcodePrintProfileService->resolve($paperProfileKey ?? request('paper_profile'));

        if ($returnType == 'json') {
            return response()->json(view('admin.items.barcodes_table', compact('item', 'paperProfiles', 'defaultPaperProfile'))->render());
        }
        return view('admin.items.barcodes_table', compact('item', 'paperProfiles', 'defaultPaperProfile'))->render();
    }

    public function getItemCode()
    {
        $lastItem = Item::orderBy('id', 'desc')->first();

        if ($lastItem) {
            $id = $lastItem->id;
        } else {
            $id = 0;
        }
        return response()->json(str_pad($id + 1, 6, '0', STR_PAD_LEFT));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Item $item
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $currentUser = $this->currentAdminUser();
        $item = Item::with('publishedBranches')->find($id);
        $categories = ItemCategory::all();
        $carats = GoldCarat::all();
        $caratTypes = GoldCaratType::all();
        $branches = $this->publicationBranchesForUser($currentUser);
        $inventoryClassifications = Item::inventoryClassificationOptions();
        $canManageBranchPublications = $branches->count() > 1;

        return view('admin.items.form', compact('item', 'categories', 'carats', 'caratTypes', 'branches', 'inventoryClassifications', 'canManageBranchPublications'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $currentUser = $this->currentAdminUser();
        $accessibleBranchIds = $this->accessiblePublicationBranchIds($currentUser);
        $requestedBranchId = (int) $request->input('branch_id');
        $defaultBranchId = ! empty($currentUser?->branch_id) ? (int) $currentUser->branch_id : $requestedBranchId;
        $branchId = $requestedBranchId ?: $defaultBranchId;

        if ($currentUser && ! $currentUser->isOwner()) {
            $branchId = in_array($requestedBranchId, $accessibleBranchIds, true)
                ? $requestedBranchId
                : ($defaultBranchId ?: ($accessibleBranchIds[0] ?? $requestedBranchId));
        }

        $validator = Validator::make(array_merge($request->all(), [
            'branch_id' => $branchId,
        ]), [
            'branch_id' => ['required', 'exists:branches,id'],
            'inventory_classification' => ['required', Rule::in(array_keys(Item::inventoryClassificationOptions()))],
            'item_type' => [
                Rule::requiredIf(fn () => $request->input('inventory_classification') === Item::CLASSIFICATION_GOLD),
                'nullable',
                'exists:gold_carat_types,id',
            ],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', 'exists:item_categories,id'],
            'carats_id' => [
                Rule::requiredIf(fn () => $request->input('inventory_classification') === Item::CLASSIFICATION_GOLD),
                'nullable',
                'exists:gold_carats,id',
            ],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'no_metal' => ['nullable', 'numeric', 'min:0'],
            'no_metal_type' => ['nullable', Rule::in(['fixed', 'percent'])],
            'labor_cost_per_gram' => ['nullable', 'numeric', 'min:0'],
            'cost_per_gram' => ['nullable', 'numeric', 'min:0'],
            'profit_margin_per_gram' => ['nullable', 'numeric', 'min:0'],
            'published_branch_ids' => ['nullable', 'array'],
            'published_branch_ids.*' => ['integer', 'exists:branches,id'],
            'branch_sale_prices' => ['nullable', 'array'],
            'branch_sale_prices.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        $validated = $validator->validated();
        $classification = $validated['inventory_classification'];

        try {
            DB::beginTransaction();
            $item = Item::updateOrCreate(['id' => $request->id ?? null], [
                'title' => ['ar' => $validated['name_ar'], 'en' => $validated['name_en'] ?? $validated['name_ar']],
                'description' => ['ar' => $validated['name_ar'], 'en' => $validated['name_en'] ?? $validated['name_ar']],
                'branch_id' => $validated['branch_id'],
                'inventory_classification' => $classification,
                'category_id' => $validated['category_id'],
                'gold_carat_id' => $classification === Item::CLASSIFICATION_GOLD ? $validated['carats_id'] : null,
                'gold_carat_type_id' => $classification === Item::CLASSIFICATION_GOLD ? $validated['item_type'] : null,
                'no_metal' => $validated['no_metal'] ?? 0,
                'no_metal_type' => $validated['no_metal_type'] ?? 'fixed',
                'labor_cost_per_gram' => $validated['labor_cost_per_gram'] ?? 0,
                'profit_margin_per_gram' => $validated['profit_margin_per_gram'] ?? 0,
            ]);

            $defaultWeight = $validated['weight'] ?? $item->defaultUnit?->weight ?? 0;
            $defaultCost = $validated['cost_per_gram'] ?? $item->defaultUnit?->average_cost_per_gram ?? 0;

            $item->defaultUnit()->updateOrCreate([
                'is_default' => true,
            ], [
                'weight' => $defaultWeight,
                'initial_cost_per_gram' => $item->defaultUnit?->initial_cost_per_gram ?? $defaultCost,
                'average_cost_per_gram' => $defaultCost,
                'current_cost_per_gram' => $defaultCost,
            ]);

            $this->syncPublishedBranches($item, $request, (int) $validated['branch_id'], $currentUser?->id);

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => __('main.saved'),
            ]);
        } catch (Throwable $ex) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'errors' => [$ex->getMessage()],
            ], 500);
        }
    }

    public function store_barcodes(Request $request, $itemId)
    {
        try {
            DB::beginTransaction();
            $item = Item::find($itemId);
            foreach ($request->weight ?? [] as $weight) {
                $item->units()->create([
                    'weight' => $weight,
                ]);
            }
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => __('main.saved'),
                'content' => $this->barcodes_table($itemId, 'html', $request->input('paper_profile')),
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
            ]);
        }
    }

    public function print_barcodes(Request $request, $itemId)
    {
        $item = Item::with(['branch', 'goldCarat', 'units'])->findOrFail($itemId);
        $paperProfile = app(BarcodePrintProfileService::class)->resolve($request->query('paper_profile'));

        return view('admin.items.print_barcode', compact('item', 'paperProfile'));
    }

    public function print_unit_barcode(Request $request, $unitId)
    {
        $unit = ItemUnit::with(['item.branch', 'item.goldCarat', 'item.goldCaratType'])->findOrFail($unitId);
        $paperProfile = app(BarcodePrintProfileService::class)->resolve($request->query('paper_profile'));

        return view('admin.items.print_barcode', compact('unit', 'paperProfile'));
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Item $item
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $item = Item::find($id);
        if ($item) {
            echo json_encode($item);
            exit;
        }
    }

    public function search(Request $request)
    {
        $code = $request->code;
        if (empty($code)) {
            return response()->json([
                'status' => false,
                'message' => __('main.required'),
                'data' => [],
            ]);
        }
        $branch_id = $request->branch_id;
        $units = ItemUnit::with([
            'item.goldCarat.tax',
            'item.goldCaratType',
            'item.defaultUnit',
            'item.branchPublications' => function ($query) use ($branch_id) {
                $query->where('branch_id', $branch_id)->where('is_active', true)->where('is_visible', true);
            },
        ])->where(function ($query) use ($code, $branch_id) {
            $query
                ->where('is_sold', 0)
                ->where(function ($q) use ($branch_id, $code) {
                    $q
                        ->where(function ($q2) use ($branch_id, $code) {
                            $q2
                                ->where('barcode', 'like', '%' . $code . '%')
                                ->whereHas('item', function ($q3) use ($branch_id) {
                                    $this->constrainItemToBranchPublication($q3, $branch_id);
                                });
                        })
                        ->orWhereHas('item', function ($q2) use ($branch_id, $code) {
                            $q2
                                ->where('title', 'like', '%' . $code . '%');
                            $this->constrainItemToBranchPublication($q2, $branch_id);
                        });
                });
        })->get();
        return response()->json([
            'status' => true,
            'data' => $this->formatUnits($units, (int) $branch_id),
        ]);
    }

    public function purchases_search(Request $request)
    {
        $caratType = $request->carat_type;
        $code = $request->code;
        if (empty($code)) {
            return response()->json([
                'status' => false,
                'message' => __('main.required'),
                'data' => [],
            ]);
        }
        $branch_id = $request->branch_id;
        $units = ItemUnit::with([
            'item.goldCarat.tax',
            'item.goldCaratType',
            'item.defaultUnit',
            'item.branchPublications' => function ($query) use ($branch_id) {
                $query->where('branch_id', $branch_id)->where('is_active', true)->where('is_visible', true);
            },
        ])->where(function ($query) use ($code, $branch_id, $caratType) {
            $query
                ->where(function ($q) use ($branch_id, $code, $caratType) {
                    $q
                        ->where(function ($q2) use ($branch_id, $code, $caratType) {
                            $q2
                                ->where('barcode', 'like', '%' . $code . '%')
                                ->whereHas('item', function ($q3) use ($branch_id, $caratType) {
                                    $this->constrainPurchaseItemToBranchAndType($q3, $branch_id, $caratType);
                                });
                        })
                        ->orWhereHas('item', function ($q2) use ($branch_id, $code, $caratType) {
                            $q2
                                ->where('title', 'like', '%' . $code . '%');
                            $this->constrainPurchaseItemToBranchAndType($q2, $branch_id, $caratType);
                        });
                });
        })->get();
        return response()->json([
            'status' => true,
            'data' => $this->formatUnitsForPurchases($units, $caratType),
        ]);
    }

    public function initial_quantities_search(Request $request)
    {
        $code = $request->code;
        if (empty($code)) {
            return response()->json([
                'status' => false,
                'message' => __('main.required'),
                'data' => [],
            ]);
        }
        $branch_id = $request->branch_id;
        $units = ItemUnit::with([
            'item.goldCarat.tax',
            'item.goldCaratType',
            'item.defaultUnit',
            'item.branchPublications' => function ($query) use ($branch_id) {
                $query->where('branch_id', $branch_id)->where('is_active', true)->where('is_visible', true);
            },
        ])->where(function ($query) use ($code, $branch_id) {
            $query
                ->where('is_sold', 0)
                ->where(function ($q) use ($branch_id, $code) {
                    $q
                        ->where(function ($q2) use ($branch_id, $code) {
                            $q2
                                ->where('barcode', 'like', '%' . $code . '%')
                                ->whereHas('item', function ($q3) use ($branch_id) {
                                    $this->constrainItemToBranchPublication($q3, $branch_id);
                                });
                        })
                        ->orWhereHas('item', function ($q2) use ($branch_id, $code) {
                            $q2
                                ->where('title', 'like', '%' . $code . '%');
                            $this->constrainItemToBranchPublication($q2, $branch_id);
                        });
                });
        })->get();
        return response()->json([
            'status' => true,
            'data' => $this->formatUnitsForPurchases($units, 'crafted', (int) $branch_id),
        ]);
    }

    private function formatUnits($units, ?int $branchId = null)
    {
        return $units->map(function ($unit) use ($branchId) {
            $taxRate = (float) ($unit->item->goldCarat?->tax?->rate ?? 0);
            $publication = $unit->item->publicationForBranch($branchId);
            $gramPrice = $publication?->sale_price_per_gram ?? $unit->gram_price;
            $gram_tax_amount = $gramPrice * $taxRate / 100;
            $caratLabel = $unit->item->goldCarat
                ? trim($unit->item->goldCarat->title . ' <br> ' . ($unit->item->goldCaratType?->title ?? ''))
                : $unit->item->inventory_classification_label;

            return [
                'unit_id' => $unit->id,
                'barcode' => $unit->barcode,
                'weight' => $unit->weight,
                'item_name' => $unit->item->title . ' <br> ' . $unit->barcode,
                'item_name_without_break' => $unit->item->title . ' ' . $unit->barcode,
                'carat' => $caratLabel,
                'gram_price' => $gramPrice,
                'gram_tax_percentage' => $taxRate,
                'gram_tax_amount' => $gram_tax_amount,
                'gram_total_amount' => $gram_tax_amount + $gramPrice,
                'carat_transform_factor' => $unit->item->goldCarat?->transform_factor ?? 1,
                'made_Value' => $unit->item->made_value,
                'no_metal' => $unit->item->no_metal,
                'quantity' => 1
            ];
        });
    }

    private function formatUnitsForPurchases($units, $caratType = 'crafted', ?int $branchId = null)
    {
        return $units->map(function ($unit) use ($caratType, $branchId) {
            $quantityBalance = $unit->item->goldCaratType?->getStock() ?? 0;
            $taxRate = ($caratType != 'crafted' || !$unit->item->goldCarat) ? 0 : (float) ($unit->item->goldCarat?->tax?->rate ?? 0);
            $caratLabel = $unit->item->goldCarat
                ? trim($unit->item->goldCarat->title . ' <br> ' . ($unit->item->goldCaratType?->title ?? ''))
                : $unit->item->inventory_classification_label;
            $publication = $unit->item->publicationForBranch($branchId);

            return [
                'unit_id' => $unit->id,
                'barcode' => $unit->barcode,
                'weight' => 0,
                'quantity_balance' => $quantityBalance,
                'item_name' => $unit->item->title . ' <br> ' . $unit->barcode,
                'item_name_without_break' => $unit->item->title . ' ' . $unit->barcode,
                'carat' => $caratLabel,
                'carat_id' => $unit->item->goldCarat?->id,
                'gram_tax_percentage' => $taxRate,
                'carat_transform_factor' => $unit->item->goldCarat?->transform_factor ?? 1,
                'made_Value' => $unit->item->made_value,
                'branch_sale_price' => $publication?->sale_price_per_gram,
            ];
        });
    }

    private function constrainItemToBranchPublication($query, $branchId)
    {
        return $query->publishedToBranch((int) $branchId);
    }

    private function constrainPurchaseItemToBranchAndType($query, $branchId, $caratType)
    {
        $this->constrainItemToBranchPublication($query, $branchId);

        if ($caratType === self::NON_GOLD_CARAT_TYPE) {
            return $query->where('inventory_classification', '!=', Item::CLASSIFICATION_GOLD);
        }

        return $query->whereHas('goldCaratType', function ($q4) use ($caratType) {
            $q4->where('key', $caratType);
        });
    }

    private function syncPublishedBranches(Item $item, Request $request, int $ownerBranchId, ?int $publisherUserId = null): void
    {
        $currentUser = $this->currentAdminUser();
        $selectedBranchIds = collect($request->input('published_branch_ids', []))
            ->filter()
            ->map(fn ($branchId) => (int) $branchId)
            ->push($ownerBranchId);

        if ($currentUser && ! $currentUser->isOwner()) {
            $allowedBranchIds = collect($this->accessiblePublicationBranchIds($currentUser));

            if ($allowedBranchIds->isNotEmpty()) {
                $selectedBranchIds = $selectedBranchIds->intersect($allowedBranchIds);
            }
        }

        $selectedBranchIds = $selectedBranchIds
            ->push($ownerBranchId)
            ->unique()
            ->values();

        $payload = [];

        foreach ($selectedBranchIds as $branchId) {
            $priceOverride = $request->input("branch_sale_prices.$branchId");
            $payload[$branchId] = [
                'is_active' => true,
                'is_visible' => true,
                'sale_price_per_gram' => $priceOverride !== null && $priceOverride !== '' ? (float) $priceOverride : null,
                'published_by_user_id' => $publisherUserId,
            ];
        }

        $item->publishedBranches()->sync($payload);
    }

    private function currentAdminUser()
    {
        return auth('admin-web')->user() ?: Auth::user();
    }

    private function publicationBranchesForUser($user)
    {
        if (! $user) {
            return Branch::where('status', 1)->get();
        }

        $branchIds = $this->accessiblePublicationBranchIds($user);

        return Branch::query()
            ->where('status', 1)
            ->when($branchIds !== [], fn ($query) => $query->whereIn('id', $branchIds))
            ->get();
    }

    /**
     * @return array<int, int>
     */
    private function accessiblePublicationBranchIds($user): array
    {
        if (! $user) {
            return [];
        }

        $branchIds = app(BranchContextService::class)->accessibleBranchIds($user);

        if ($branchIds === [] && ! empty($user->branch_id)) {
            return [(int) $user->branch_id];
        }

        return $branchIds;
    }
}
