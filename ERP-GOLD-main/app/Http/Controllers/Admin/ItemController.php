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
use App\Services\Items\DefaultItemSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
                    return $this->localizedAttribute($row, 'title');
                })
                ->addColumn('status', function ($row) {
                    $row->status ? $span = 'متاح' : $span = 'غير متاح';
                    return $span;
                })
                ->addColumn('inventory_classification', function ($row) {
                    return $row->inventory_classification_label;
                })
                ->addColumn('category', function ($row) {
                    return $this->localizedAttribute($row->category, 'title');
                })
                ->addColumn('gold_carat', function ($row) {
                    return $this->localizedAttribute($row->goldCarat, 'title');
                })
                ->addColumn('gold_carat_type', function ($row) {
                    return $this->localizedAttribute($row->goldCaratType, 'title');
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

                    if ($row->sale_mode === Item::SALE_MODE_SINGLE) {
                        $btn = $btn . '<a href=' . route('items.barcode_table', $row->id) . ' class="btn btn-labeled btn-warning showBarcodeTable"
                                value="' . $row->id . '" role="button" target="_blank" ><i class="fa fa-barcode"></i>
                            </a>';
                    }

                    $btn = $btn . '<button type="button" class="btn btn-labeled btn-danger deleteBtn "
                            value="' . $row->id . '"><i class="fa fa-trash"></i>
                        </button>';

                    return $btn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        $categories = ItemCategory::query()->orderBy('id')->get();
        $carats = GoldCarat::all();
        $branches = $this->publicationBranchesForUser($currentUser);
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
        $categories = ItemCategory::query()->orderBy('id')->get();
        $carats = GoldCarat::all();
        $caratTypes = GoldCaratType::all();
        $branches = $this->publicationBranchesForUser($currentUser);
        $inventoryClassifications = Item::inventoryClassificationOptions();
        $saleModes = Item::saleModeOptions();
        $canManageBranchPublications = $branches->count() > 1;
        $collectibleDefaultCaratId = $this->resolveCollectibleDefaultCaratId($carats);
        $silverDefaultCaratId = $this->resolveSilverDefaultCaratId($carats);
        $itemDefaults = app(DefaultItemSettingsService::class)->currentSettings();

        return view('admin.items.form', compact('categories', 'carats', 'caratTypes', 'branches', 'inventoryClassifications', 'saleModes', 'canManageBranchPublications', 'collectibleDefaultCaratId', 'silverDefaultCaratId', 'itemDefaults'));
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
        $item = Item::with(['publishedBranches', 'units'])->find($id);
        $categories = ItemCategory::query()->orderBy('id')->get();
        $carats = GoldCarat::all();
        $caratTypes = GoldCaratType::all();
        $branches = $this->publicationBranchesForUser($currentUser);
        $inventoryClassifications = Item::inventoryClassificationOptions();
        $saleModes = Item::saleModeOptions();
        $canManageBranchPublications = $branches->count() > 1;
        $lockWeightField = $item?->sale_mode === Item::SALE_MODE_SINGLE && $item->units->where('is_sold', false)->isNotEmpty();
        $collectibleDefaultCaratId = $this->resolveCollectibleDefaultCaratId($carats);
        $silverDefaultCaratId = $this->resolveSilverDefaultCaratId($carats);

        return view('admin.items.form', compact('item', 'categories', 'carats', 'caratTypes', 'branches', 'inventoryClassifications', 'saleModes', 'canManageBranchPublications', 'lockWeightField', 'collectibleDefaultCaratId', 'silverDefaultCaratId'));
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

        $itemId = filled($request->input('id')) ? (int) $request->input('id') : null;

        $validator = Validator::make(array_merge($request->all(), [
            'branch_id' => $branchId,
        ]), [
            'branch_id' => ['required', 'exists:branches,id'],
            'inventory_classification' => ['required', Rule::in(array_keys(Item::inventoryClassificationOptions()))],
            'sale_mode' => ['required', Rule::in(array_keys(Item::saleModeOptions()))],
            'item_type' => [
                Rule::requiredIf(fn () => $request->input('inventory_classification') === Item::CLASSIFICATION_GOLD),
                'nullable',
                'exists:gold_carat_types,id',
            ],
            'name_ar' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($branchId, $itemId) {
                    if ($this->branchAlreadyHasItemNamed((string) $value, $branchId, $itemId)) {
                        $fail('اسم الصنف مستخدم مسبقًا داخل هذا الفرع.');
                    }
                },
            ],
            'name_en' => ['nullable', 'string', 'max:255'],
            'category_id' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! ItemCategory::query()->whereKey($value)->exists()) {
                        $fail('مجموعة الصنف المختارة غير متاحة لهذا المشترك.');
                    }
                },
            ],
            'carats_id' => [
                Rule::requiredIf(fn () => $request->input('inventory_classification') === Item::CLASSIFICATION_GOLD),
                'nullable',
                'exists:gold_carats,id',
            ],
            'weight' => [
                Rule::requiredIf(fn () => $request->input('sale_mode') === Item::SALE_MODE_SINGLE),
                'nullable',
                'numeric',
                Rule::when(
                    fn () => $request->input('sale_mode') === Item::SALE_MODE_SINGLE,
                    ['gt:0'],
                    ['min:0']
                ),
            ],
            'no_metal' => ['nullable', 'numeric', 'min:0'],
            'no_metal_type' => ['nullable', Rule::in(['fixed', 'percent'])],
            'labor_cost_per_gram' => ['nullable', 'numeric', 'min:0'],
            'cost_per_gram' => ['nullable', 'numeric', 'min:0'],
            'profit_margin_per_gram' => ['nullable', 'numeric', 'min:0'],
            'published_branch_ids' => ['nullable', 'array'],
            'published_branch_ids.*' => ['integer', 'exists:branches,id'],
            'branch_sale_prices' => ['nullable', 'array'],
            'branch_sale_prices.*' => ['nullable', 'numeric', 'min:0'],
            // حقول المقتنيات والفضة
            'stone_type_1' => ['nullable', 'string', 'max:255'],
            'stone_type_2' => ['nullable', 'string', 'max:255'],
            'stone_size_1' => ['nullable', 'string', 'max:255'],
            'stone_size_2' => ['nullable', 'string', 'max:255'],
            'stone_clarity' => ['nullable', 'string', 'max:255'],
            'stone_color' => ['nullable', 'string', 'max:255'],
            'gold_weight_18k' => ['nullable', 'numeric', 'min:0'],
            'metal_notes' => ['nullable', 'string'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model_number' => ['nullable', 'string', 'max:255'],
            'country_of_origin' => ['nullable', 'string', 'max:255'],
            'impurity_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'certificate_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        $validated = $validator->validated();
        $classification = $validated['inventory_classification'];
        $saleMode = $validated['sale_mode'];
        $collectibleDefaultCaratId = $classification === Item::CLASSIFICATION_COLLECTIBLE
            ? $this->resolveCollectibleDefaultCaratId()
            : null;
        $silverDefaultCaratId = $classification === Item::CLASSIFICATION_SILVER
            ? $this->resolveSilverDefaultCaratId()
            : null;

        if ($classification === Item::CLASSIFICATION_COLLECTIBLE && ! $collectibleDefaultCaratId) {
            return response()->json([
                'status' => false,
                'errors' => ['تعذر العثور على عيار 18 اللازم لحفظ أصناف المقتنيات.'],
            ], 422);
        }

        if ($classification === Item::CLASSIFICATION_SILVER && ! $silverDefaultCaratId) {
            return response()->json([
                'status' => false,
                'errors' => ['تعذر العثور على عيار 925 اللازم لحفظ أصناف الفضة.'],
            ], 422);
        }

        try {
            DB::beginTransaction();
            // معالجة ملف الشهادة
            $certificateFileName = null;
            if ($request->hasFile('certificate_file')) {
                $certDir = public_path('uploads/certificates');
                if (! is_dir($certDir)) {
                    mkdir($certDir, 0755, true);
                }
                $certFile = $request->file('certificate_file');
                $certificateFileName = uniqid('cert_') . '.' . $certFile->getClientOriginalExtension();
                $certFile->move($certDir, $certificateFileName);
            }

            $isCollectibleOrSilver = in_array($classification, [Item::CLASSIFICATION_COLLECTIBLE, Item::CLASSIFICATION_SILVER], true);

            $itemData = [
                'title' => ['ar' => $validated['name_ar'], 'en' => $validated['name_en'] ?? $validated['name_ar']],
                'description' => ['ar' => $validated['name_ar'], 'en' => $validated['name_en'] ?? $validated['name_ar']],
                'branch_id' => $validated['branch_id'],
                'inventory_classification' => $classification,
                'sale_mode' => $saleMode,
                'category_id' => $validated['category_id'],
                'gold_carat_id' => $classification === Item::CLASSIFICATION_GOLD
                    ? ($validated['carats_id'] ?? null)
                    : ($classification === Item::CLASSIFICATION_SILVER
                        ? $silverDefaultCaratId
                        : ($classification === Item::CLASSIFICATION_COLLECTIBLE ? $collectibleDefaultCaratId : null)),
                'gold_carat_type_id' => $classification === Item::CLASSIFICATION_GOLD ? ($validated['item_type'] ?? null) : null,
                'no_metal' => $validated['no_metal'] ?? 0,
                'no_metal_type' => $validated['no_metal_type'] ?? 'fixed',
                'labor_cost_per_gram' => $validated['labor_cost_per_gram'] ?? 0,
                'profit_margin_per_gram' => $validated['profit_margin_per_gram'] ?? 0,
            ];

            if ($isCollectibleOrSilver) {
                $itemData['stone_type_1'] = $validated['stone_type_1'] ?? null;
                $itemData['stone_type_2'] = $validated['stone_type_2'] ?? null;
                $itemData['stone_size_1'] = $validated['stone_size_1'] ?? null;
                $itemData['stone_size_2'] = $validated['stone_size_2'] ?? null;
                $itemData['stone_clarity'] = $validated['stone_clarity'] ?? null;
                $itemData['stone_color'] = $validated['stone_color'] ?? null;
                $itemData['gold_weight_18k'] = $classification === Item::CLASSIFICATION_COLLECTIBLE ? ($validated['gold_weight_18k'] ?? 0) : 0;
                $itemData['metal_notes'] = $validated['metal_notes'] ?? null;
                $itemData['brand'] = $validated['brand'] ?? null;
                $itemData['model_number'] = $validated['model_number'] ?? null;
                $itemData['country_of_origin'] = $validated['country_of_origin'] ?? null;
                $itemData['impurity_percentage'] = $validated['impurity_percentage'] ?? 0;
                if ($certificateFileName) {
                    $itemData['certificate_file'] = $certificateFileName;
                }
            }

            $item = Item::updateOrCreate(['id' => $request->id ?? null], $itemData);

            $existingDefaultUnit = $item->defaultUnit()->first();
            $defaultWeight = $saleMode === Item::SALE_MODE_SINGLE
                ? ($validated['weight'] ?? $existingDefaultUnit?->weight ?? 0)
                : 0;
            $defaultCost = $validated['cost_per_gram'] ?? $item->defaultUnit?->average_cost_per_gram ?? 0;

            $item->defaultUnit()->updateOrCreate([
                'is_default' => true,
            ], [
                'weight' => $defaultWeight,
                'initial_cost_per_gram' => $item->defaultUnit?->initial_cost_per_gram ?? $defaultCost,
                'average_cost_per_gram' => $defaultCost,
                'current_cost_per_gram' => $defaultCost,
            ]);

            if ($saleMode === Item::SALE_MODE_REPEATABLE) {
                $item->units()->where('is_sold', false)->delete();
            } else {
                $hasUnsoldBarcodeUnits = $item->units()->where('is_sold', false)->exists();
                $defaultUnit = $item->defaultUnit()->first();
                $hasLegacyDefaultBarcode = filled($defaultUnit?->barcode) && ! $defaultUnit?->is_sold;

                if (! $hasUnsoldBarcodeUnits && ! $hasLegacyDefaultBarcode) {
                    $item->units()->create([
                        'weight' => $defaultWeight,
                    ]);
                }
            }

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
        $item = Item::findOrFail($itemId);

        if ($item->sale_mode !== Item::SALE_MODE_SINGLE) {
            return response()->json([
                'status' => false,
                'errors' => ['الباركودات متاحة فقط للأصناف التي تباع مرة واحدة.'],
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'weight' => ['required', 'array', 'min:1'],
            'weight.*' => ['required', 'numeric', 'gt:0'],
        ], [
            'weight.required' => 'أضف وزنًا واحدًا على الأقل قبل حفظ الباركودات.',
            'weight.array' => 'صيغة أوزان الباركودات غير صحيحة.',
            'weight.min' => 'أضف وزنًا واحدًا على الأقل قبل حفظ الباركودات.',
            'weight.*.required' => 'لا يمكن إنشاء باركود بدون وزن.',
            'weight.*.numeric' => 'وزن الباركود يجب أن يكون رقمًا صالحًا.',
            'weight.*.gt' => 'وزن الباركود يجب أن يكون أكبر من صفر.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        try {
            DB::beginTransaction();
            foreach ($validator->validated()['weight'] as $weight) {
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
        } catch (Throwable $ex) {
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

    public function lost_barcodes(Request $request)
    {
        $currentUser = $this->currentAdminUser();
        $branches = $this->publicationBranchesForUser($currentUser);
        $selectedBranchId = $this->resolveLostBarcodeBranchId($request, $currentUser, $branches);
        $paperProfiles = app(BarcodePrintProfileService::class)->all();
        $defaultPaperProfile = app(BarcodePrintProfileService::class)->resolve($request->query('paper_profile'));

        return view('admin.items.lost_barcodes', compact('branches', 'selectedBranchId', 'paperProfiles', 'defaultPaperProfile'));
    }

    public function lost_barcodes_search(Request $request)
    {
        $currentUser = $this->currentAdminUser();
        $branches = $this->publicationBranchesForUser($currentUser);
        $allowedBranchIds = $branches->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $requestedBranchId = filled($request->input('branch_id')) ? (int) $request->input('branch_id') : null;

        if ($requestedBranchId && $allowedBranchIds !== [] && ! in_array($requestedBranchId, $allowedBranchIds, true)) {
            abort(403);
        }

        $selectedBranchId = $this->resolveLostBarcodeBranchId($request, $currentUser, $branches);

        $validator = Validator::make([
            'branch_id' => $selectedBranchId,
            'weight' => $request->input('weight'),
        ], [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'weight' => ['required', 'numeric', 'gt:0'],
        ], [
            'branch_id.required' => 'اختر الفرع أولًا قبل البحث عن الباركود.',
            'weight.required' => 'أدخل وزن القطعة قبل البحث.',
            'weight.numeric' => 'الوزن يجب أن يكون رقمًا صالحًا.',
            'weight.gt' => 'الوزن يجب أن يكون أكبر من صفر.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()->all(),
                'data' => [],
            ], 422);
        }

        $validated = $validator->validated();
        $searchWeight = round((float) $validated['weight'], 3);
        $tolerance = 0.05;
        $minWeight = max(0, $searchWeight - $tolerance);
        $maxWeight = $searchWeight + $tolerance;

        $units = ItemUnit::query()
            ->with([
                'item.branch',
                'item.goldCarat',
                'item.goldCaratType',
            ])
            ->where('is_sold', false)
            ->where('weight', '>', 0)
            ->whereNotNull('barcode')
            ->where('barcode', '!=', '')
            ->whereBetween('weight', [$minWeight, $maxWeight])
            ->whereHas('item', function ($itemQuery) use ($validated) {
                $branchId = (int) $validated['branch_id'];

                $itemQuery
                    ->where(function ($visibilityQuery) use ($branchId) {
                        $visibilityQuery->publishedToBranch($branchId);
                        $visibilityQuery->orWhere('branch_id', $branchId);
                    });
            })
            ->orderByRaw('ABS(weight - ?) asc', [$searchWeight])
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return response()->json([
            'status' => true,
            'message' => $units->isEmpty()
                ? 'لا توجد قطع مطابقة للوزن المدخل.'
                : 'تم العثور على القطع المطابقة، اختر القطعة ثم أعد طباعة باركودها.',
            'data' => $this->formatLostBarcodeUnits($units),
        ]);
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
        $caratType = $this->normalizeSalesCaratType($request->carat_type);
        $code = $request->code;
        if (empty($code)) {
            return response()->json([
                'status' => false,
                'message' => __('main.required'),
                'data' => [],
            ]);
        }
        $branch_id = (int) $request->branch_id;
        $items = Item::query()
            ->with($this->searchItemRelations($branch_id))
            ->where(function ($query) use ($code) {
                $query
                    ->where('title', 'like', '%' . $code . '%')
                    ->orWhere('code', 'like', '%' . $code . '%')
                    ->orWhereHas('defaultUnit', function ($unitQuery) use ($code) {
                        $unitQuery->where('barcode', 'like', '%' . $code . '%');
                    })
                    ->orWhereHas('units', function ($unitQuery) use ($code) {
                        $unitQuery->where('is_sold', false)->where('barcode', 'like', '%' . $code . '%');
                    });
            });

        $this->constrainSaleItemToBranchAndType($items, $branch_id, $caratType);

        $units = $this->resolveSearchUnits($items->get(), (string) $code);

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
        $branch_id = (int) $request->branch_id;
        $items = Item::query()
            ->with($this->searchItemRelations($branch_id))
            ->where(function ($query) use ($code) {
                $query
                    ->where('title', 'like', '%' . $code . '%')
                    ->orWhere('code', 'like', '%' . $code . '%')
                    ->orWhereHas('defaultUnit', function ($unitQuery) use ($code) {
                        $unitQuery->where('barcode', 'like', '%' . $code . '%');
                    })
                    ->orWhereHas('units', function ($unitQuery) use ($code) {
                        $unitQuery->where('is_sold', false)->where('barcode', 'like', '%' . $code . '%');
                    });
            });

        $this->constrainPurchaseItemToBranchAndType($items, $branch_id, $caratType);

        $units = $this->resolveSearchUnits($items->get(), (string) $code);

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
        $branch_id = (int) $request->branch_id;
        $items = Item::query()
            ->with($this->searchItemRelations($branch_id))
            ->where(function ($query) use ($code) {
                $query
                    ->where('title', 'like', '%' . $code . '%')
                    ->orWhere('code', 'like', '%' . $code . '%')
                    ->orWhereHas('defaultUnit', function ($unitQuery) use ($code) {
                        $unitQuery->where('barcode', 'like', '%' . $code . '%');
                    })
                    ->orWhereHas('units', function ($unitQuery) use ($code) {
                        $unitQuery->where('is_sold', false)->where('barcode', 'like', '%' . $code . '%');
                    });
            });

        $this->constrainItemToBranchPublication($items, $branch_id);

        $units = $this->resolveSearchUnits($items->get(), (string) $code);

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
            $barcodeLabel = $this->formattedBarcodeLabel($unit);

            return [
                'unit_id' => $unit->id,
                'barcode' => $unit->barcode,
                'weight' => $unit->weight,
                'item_name' => $this->formattedItemSearchLabel($unit->item->title, $barcodeLabel, true),
                'item_name_without_break' => $this->formattedItemSearchLabel($unit->item->title, $barcodeLabel, false),
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
            $barcodeLabel = $this->formattedBarcodeLabel($unit);

            return [
                'unit_id' => $unit->id,
                'barcode' => $unit->barcode,
                'weight' => (float) $unit->weight,
                'quantity_balance' => $quantityBalance,
                'item_name' => $this->formattedItemSearchLabel($unit->item->title, $barcodeLabel, true),
                'item_name_without_break' => $this->formattedItemSearchLabel($unit->item->title, $barcodeLabel, false),
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

    private function constrainSaleItemToBranchAndType($query, $branchId, string $caratType)
    {
        $this->constrainItemToBranchPublication($query, $branchId);

        return $query->whereHas('goldCaratType', function ($caratTypeQuery) use ($caratType) {
            $caratTypeQuery->where('key', $caratType);
        });
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

    private function normalizeSalesCaratType(?string $caratType): string
    {
        return in_array($caratType, ['crafted', 'scrap', 'pure'], true) ? $caratType : 'crafted';
    }

    private function searchItemRelations(int $branchId): array
    {
        return [
            'goldCarat.tax',
            'goldCaratType',
            'defaultUnit',
            'units' => function ($query) {
                $query->where('is_sold', false)->orderBy('id');
            },
            'branchPublications' => function ($query) use ($branchId) {
                $query->where('branch_id', $branchId)->where('is_active', true)->where('is_visible', true);
            },
        ];
    }

    private function resolveSearchUnits($items, string $code)
    {
        $normalizedCode = mb_strtolower(trim($code));

        return $items->flatMap(function (Item $item) use ($normalizedCode) {
            $matchesTitle = $this->itemMatchesSearchTerm($item, $normalizedCode);

            if ($item->sale_mode === Item::SALE_MODE_REPEATABLE) {
                return $matchesTitle && $item->defaultUnit
                    ? collect([$item->defaultUnit])
                    : collect();
            }

            $barcodeUnits = $item->units->filter(fn (ItemUnit $unit) => ! $unit->is_sold);

            if ($barcodeUnits->isNotEmpty()) {
                return $barcodeUnits
                    ->filter(fn (ItemUnit $unit) => $matchesTitle || $this->barcodeMatches((string) $unit->barcode, $normalizedCode))
                    ->values();
            }

            $defaultUnit = $item->defaultUnit;

            if (! $defaultUnit || $defaultUnit->is_sold) {
                return collect();
            }

            return ($matchesTitle || $this->barcodeMatches((string) $defaultUnit->barcode, $normalizedCode))
                ? collect([$defaultUnit])
                : collect();
        })->values();
    }

    private function itemMatchesSearchTerm(Item $item, string $normalizedCode): bool
    {
        $needles = array_filter([
            mb_strtolower((string) $item->getTranslation('title', 'ar')),
            mb_strtolower((string) $item->getTranslation('title', 'en')),
            mb_strtolower((string) $item->code),
        ]);

        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($needle, $normalizedCode)) {
                return true;
            }
        }

        return false;
    }

    private function barcodeMatches(string $barcode, string $normalizedCode): bool
    {
        return $barcode !== '' && str_contains(mb_strtolower($barcode), $normalizedCode);
    }

    private function formattedBarcodeLabel(ItemUnit $unit): string
    {
        if ($unit->item->sale_mode === Item::SALE_MODE_REPEATABLE) {
            return '';
        }

        return trim((string) $unit->barcode);
    }

    private function formattedItemSearchLabel($itemTitle, string $barcode, bool $withLineBreak = true): string
    {
        $itemTitle = trim((string) $itemTitle);

        if ($barcode === '') {
            return $itemTitle;
        }

        return $withLineBreak
            ? $itemTitle . ' <br> ' . $barcode
            : $itemTitle . ' ' . $barcode;
    }

    private function formatLostBarcodeUnits(Collection $units): array
    {
        return $units->map(function (ItemUnit $unit) {
            $item = $unit->item;
            $branchName = $item->branch?->getTranslation('name', 'ar') ?? $item->branch?->name ?? '-';

            if ($item->inventory_classification === Item::CLASSIFICATION_GOLD) {
                $caratLabel = trim(implode(' - ', array_filter([
                    $item->goldCarat?->getTranslation('title', 'ar') ?? $item->goldCarat?->title,
                    $item->goldCaratType?->title,
                ])));
            } elseif ($item->inventory_classification === Item::CLASSIFICATION_SILVER) {
                $caratLabel = 'فضة - ' . ($item->goldCarat?->getTranslation('title', 'ar') ?? $item->goldCarat?->title ?? '925');
            } elseif ($item->inventory_classification === Item::CLASSIFICATION_COLLECTIBLE) {
                $caratLabel = 'مقتنيات - ' . ($item->goldCarat?->getTranslation('title', 'ar') ?? $item->goldCarat?->title ?? 'عيار 18');
            } else {
                $caratLabel = $item->inventory_classification_label;
            }

            return [
                'unit_id' => $unit->id,
                'item_code' => $item->code,
                'barcode' => $unit->barcode,
                'weight' => number_format((float) $unit->weight, 3),
                'name_ar' => $item->getTranslation('title', 'ar'),
                'name_en' => $item->getTranslation('title', 'en'),
                'branch_name' => $branchName,
                'carat_label' => $caratLabel,
                'print_url' => route('items.units.print_barcode', $unit->id, false),
            ];
        })->values()->all();
    }

    private function localizedAttribute($model, string $attribute): string
    {
        if (! $model) {
            return '-';
        }

        if (method_exists($model, 'getTranslation')) {
            $translated = $model->getTranslation($attribute, app()->getLocale());
            $normalized = $this->localizedValue($translated);

            if ($normalized !== '-') {
                return $normalized;
            }
        }

        return $this->localizedValue($model->{$attribute} ?? null);
    }

    private function localizedValue($value): string
    {
        if (is_array($value)) {
            $locale = app()->getLocale();
            $value = $value[$locale] ?? $value['ar'] ?? $value['en'] ?? reset($value);
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            $decoded = json_decode($trimmed, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->localizedValue($decoded);
            }

            return $trimmed !== '' ? $trimmed : '-';
        }

        if ($value === null) {
            return '-';
        }

        return (string) $value;
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

    private function resolveLostBarcodeBranchId(Request $request, $user, ?Collection $branches = null): ?int
    {
        $branches = $branches ?? $this->publicationBranchesForUser($user);
        $allowedBranchIds = $branches->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $requestedBranchId = filled($request->input('branch_id')) ? (int) $request->input('branch_id') : null;

        if ($requestedBranchId && ($allowedBranchIds === [] || in_array($requestedBranchId, $allowedBranchIds, true))) {
            return $requestedBranchId;
        }

        if ($user) {
            $currentBranchId = app(BranchContextService::class)->currentBranchId($user, $request->session());

            if ($currentBranchId && ($allowedBranchIds === [] || in_array($currentBranchId, $allowedBranchIds, true))) {
                return $currentBranchId;
            }

            if (! empty($user->branch_id) && ($allowedBranchIds === [] || in_array((int) $user->branch_id, $allowedBranchIds, true))) {
                return (int) $user->branch_id;
            }
        }

        return $allowedBranchIds[0] ?? null;
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

    private function resolveCollectibleDefaultCaratId(?Collection $carats = null): ?int
    {
        return $this->resolveCaratIdByMarker('18', $carats);
    }

    private function resolveSilverDefaultCaratId(?Collection $carats = null): ?int
    {
        return $this->resolveCaratIdByMarker('925', $carats);
    }

    private function resolveCaratIdByMarker(string $marker, ?Collection $carats = null): ?int
    {
        $carats = $carats ?? GoldCarat::query()->get();

        $carat = $carats->first(function (GoldCarat $carat) use ($marker) {
            $candidates = [
                (string) $carat->label,
                (string) $carat->getTranslation('title', 'ar'),
                (string) $carat->getTranslation('title', 'en'),
            ];

            foreach ($candidates as $candidate) {
                if (preg_match('/(^|[^0-9])' . preg_quote($marker, '/') . '([^0-9]|$)/u', $candidate)) {
                    return true;
                }
            }

            return false;
        });

        return $carat?->id ? (int) $carat->id : null;
    }

    private function branchAlreadyHasItemNamed(string $itemName, int $branchId, ?int $ignoredItemId = null): bool
    {
        $normalizedName = $this->normalizeItemName($itemName);

        return Item::query()
            ->where('branch_id', $branchId)
            ->when($ignoredItemId, fn ($query) => $query->whereKeyNot($ignoredItemId))
            ->get()
            ->contains(function (Item $item) use ($normalizedName) {
                return $this->normalizeItemName((string) $item->getTranslation('title', 'ar')) === $normalizedName;
            });
    }

    private function normalizeItemName(string $itemName): string
    {
        return preg_replace('/\s+/u', ' ', trim($itemName)) ?? '';
    }
}
