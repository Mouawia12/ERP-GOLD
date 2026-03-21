<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\GoldCarat;
use App\Models\GoldCaratType;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use DataTables;

class ItemController extends Controller
{
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
        $data = Item::all();

        if (!empty(Auth::user()->branch_id)) {
            $data = $data->where('branch_id', Auth::user()->branch_id);
        }

        if ($request->ajax()) {
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('status', function ($row) {
                    $row->status ? $span = 'متاح' : $span = 'غير متاح';
                    return $span;
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

        return view('admin.items.index', compact('categories', 'carats', 'branches'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = ItemCategory::all();
        $carats = GoldCarat::all();
        $caratTypes = GoldCaratType::all();
        $branches = Branch::where('status', 1)->get();

        return view('admin.items.form', compact('categories', 'carats', 'caratTypes', 'branches'));
    }

    public function barcodes_table($itemId, $returnType = 'json')
    {
        $item = Item::find($itemId);
        if ($returnType == 'json') {
            return response()->json(view('admin.items.barcodes_table', compact('item'))->render());
        }
        return view('admin.items.barcodes_table', compact('item'))->render();
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
        $item = Item::find($id);
        $categories = ItemCategory::all();
        $carats = GoldCarat::all();
        $caratTypes = GoldCaratType::all();
        $branches = Branch::where('status', 1)->get();

        return view('admin.items.form', compact('item', 'categories', 'carats', 'caratTypes', 'branches'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $item = Item::updateOrCreate(['id' => $request->id ?? null], [
                'title' => ['ar' => $request->name_ar, 'en' => $request->name_en],
                'description' => ['ar' => $request->name_ar, 'en' => $request->name_en],
                'branch_id' => $request->branch_id,
                'category_id' => $request->category_id,
                'gold_carat_id' => $request->carats_id,
                'gold_carat_type_id' => $request->item_type,
                'weight' => $request->weight ?? 0,
                'no_metal' => $request->no_metal ?? 0,
                'no_metal_type' => $request->no_metal_type ?? 0,
                'labor_cost_per_gram' => $request->labor_cost_per_gram ?? 0,
                'profit_margin_per_gram' => $request->profit_margin_per_gram ?? 0,
            ]);

            if (is_null($item->defaultUnit)) {
                $item->defaultUnit()->create([
                    'weight' => $request->weight ?? 0,
                    'initial_cost_per_gram' => $request->cost_per_gram ?? 0,
                    'average_cost_per_gram' => $request->cost_per_gram ?? 0,
                    'current_cost_per_gram' => $request->cost_per_gram ?? 0,
                    'is_default' => true,
                ]);
            }

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => __('main.saved'),
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
            ]);
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
                'content' => $this->barcodes_table($itemId, 'html'),
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
            ]);
        }
    }

    public function print_barcodes($itemId)
    {
        $item = Item::find($itemId);
        return view('admin.items.print_barcode', compact('item'));
    }

    public function print_unit_barcode($unitId)
    {
        $unit = ItemUnit::find($unitId);
        return view('admin.items.print_barcode', compact('unit'));
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
        $units = ItemUnit::where(function ($query) use ($code, $branch_id) {
            $query
                ->where('is_sold', 0)
                ->where(function ($q) use ($branch_id, $code) {
                    $q
                        ->where(function ($q2) use ($branch_id, $code) {
                            $q2
                                ->where('barcode', 'like', '%' . $code . '%')
                                ->whereHas('item', function ($q3) use ($branch_id) {
                                    $q3->where('branch_id', $branch_id);
                                });
                        })
                        ->orWhereHas('item', function ($q2) use ($branch_id, $code) {
                            $q2
                                ->where('branch_id', $branch_id)
                                ->where('title', 'like', '%' . $code . '%');
                        });
                });
        })->get();
        return response()->json([
            'status' => true,
            'data' => $this->formatUnits($units),
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
        $units = ItemUnit::where(function ($query) use ($code, $branch_id, $caratType) {
            $query
                ->where(function ($q) use ($branch_id, $code, $caratType) {
                    $q
                        ->where(function ($q2) use ($branch_id, $code, $caratType) {
                            $q2
                                ->where('barcode', 'like', '%' . $code . '%')
                                ->whereHas('item', function ($q3) use ($branch_id, $caratType) {
                                    $q3->where('branch_id', $branch_id)->whereHas('goldCaratType', function ($q4) use ($caratType) {
                                        $q4->where('key', $caratType);
                                    });
                                });
                        })
                        ->orWhereHas('item', function ($q2) use ($branch_id, $code, $caratType) {
                            $q2
                                ->where('branch_id', $branch_id)
                                ->where('title', 'like', '%' . $code . '%')
                                ->whereHas('goldCaratType', function ($q4) use ($caratType) {
                                    $q4->where('key', $caratType);
                                });
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
        $units = ItemUnit::where(function ($query) use ($code, $branch_id) {
            $query
                ->where('is_sold', 0)
                ->where(function ($q) use ($branch_id, $code) {
                    $q
                        ->where(function ($q2) use ($branch_id, $code) {
                            $q2
                                ->where('barcode', 'like', '%' . $code . '%')
                                ->whereHas('item', function ($q3) use ($branch_id) {
                                    $q3->where('branch_id', $branch_id);
                                });
                        })
                        ->orWhereHas('item', function ($q2) use ($branch_id, $code) {
                            $q2
                                ->where('branch_id', $branch_id)
                                ->where('title', 'like', '%' . $code . '%');
                        });
                });
        })->get();
        return response()->json([
            'status' => true,
            'data' => $this->formatUnitsForPurchases($units),
        ]);
    }

    private function formatUnits($units)
    {
        return $units->map(function ($unit) {
            $gram_tax_amount = $unit->gram_price * $unit->item->goldCarat->tax->rate / 100;
            return [
                'unit_id' => $unit->id,
                'barcode' => $unit->barcode,
                'weight' => $unit->weight,
                'item_name' => $unit->item->title . ' <br> ' . $unit->barcode,
                'item_name_without_break' => $unit->item->title . ' ' . $unit->barcode,
                'carat' => $unit->item->goldCarat->title . ' <br> ' . $unit->item->goldCaratType->title,
                'gram_price' => $unit->gram_price,
                'gram_tax_percentage' => $unit->item->goldCarat->tax->rate,
                'gram_tax_amount' => $gram_tax_amount,
                'gram_total_amount' => $gram_tax_amount + $unit->gram_price,
                'carat_transform_factor' => $unit->item->goldCarat->transform_factor,
                'made_Value' => $unit->item->made_value,
                'no_metal' => $unit->item->no_metal,
                'quantity' => 1
            ];
        });
    }

    private function formatUnitsForPurchases($units, $caratType = 'crafted')
    {
        return $units->map(function ($unit) use ($caratType) {
            $quantityBalance = $unit->item->goldCaratType->getStock();
            $taxRate = ($caratType != 'crafted') ? 0 : $unit->item->goldCarat->tax->rate;
            return [
                'unit_id' => $unit->id,
                'barcode' => $unit->barcode,
                'weight' => 0,
                'quantity_balance' => $quantityBalance,
                'item_name' => $unit->item->title . ' <br> ' . $unit->barcode,
                'item_name_without_break' => $unit->item->title . ' ' . $unit->barcode,
                'carat' => $unit->item->goldCarat->title . ' <br> ' . $unit->item->goldCaratType->title,
                'carat_id' => $unit->item->goldCarat->id,
                'gram_tax_percentage' => $taxRate,
                'carat_transform_factor' => $unit->item->goldCarat->transform_factor,
                'made_Value' => $unit->item->made_value,
            ];
        });
    }
}
