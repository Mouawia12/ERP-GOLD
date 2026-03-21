<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoldCarat;
use App\Models\GoldPrice;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CaratController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $karats = Karat::all();
        $pricings = Pricing::all();
        return view('admin.Karat.index', compact('karats'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if ($request->id == 0) {
            $validated = $request->validate([
                'name_ar' => 'required|unique:karats',
                'name_en' => 'required|unique:karats',
                'label' => 'required|unique:karats',
                'transform_factor' => 'required'
            ]);
            try {
                Karat::create([
                    'name_ar' => $request->name_ar,
                    'name_en' => $request->name_en,
                    'label' => $request->label,
                    'stamp_value' => $request->stamp_value ?? 0,
                    'transform_factor' => $request->transform_factor ?? 1
                ]);

                /*
                 * Karat2::create([
                 *     'name_ar' => $request -> name_ar,
                 *     'name_en' => $request -> name_en,
                 *     'label' => $request -> label ,
                 *     'stamp_value' => $request -> stamp_value ?? 0,
                 *     'transform_factor' => $request -> transform_factor ?? 1
                 * ]);
                 */
                return redirect()->route('karats')->with('success', __('main.created'));
            } catch (QueryException $ex) {
                return redirect()->route('karats')->with('error', $ex->getMessage());
            }
        } else {
            return $this->update($request);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Karat  $karat
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $price = GoldPrice::all()->first();
        $carat = GoldCarat::FindOrFail($id);

        return response()->json([
            'tax_percentage' => $carat->tax->rate . '%',
        ]);
    }

    public function show_old($id)
    {
        $price = Pricing::all()->first();
        $karat = Karat::FindOrFail($id);
        $oldWarehouses = Warehouse::select(DB::raw('sum(enter_weight - out_weight) as balance'))
            ->where('type', '=', 0)
            ->where('karat_id', $karat->id)
            ->get()
            ->first();

        if ($karat) {
            $karat->price = $price->price_21 * $karat->transform_factor;
            $karat->balance_net = $oldWarehouses->balance;
            echo json_encode($karat);
            exit;
        }
    }

    public function show_pure($id)
    {
        $price = Pricing::all()->first();
        $karat = Karat::FindOrFail($id);
        $oldWarehouses = Warehouse::select(DB::raw('sum(enter_weight - out_weight) as balance'))
            ->where('type', '=', 2)
            ->get()
            ->first();

        if ($karat) {
            $karat->price = $price->price_21 * $karat->transform_factor;
            $karat->balance_net = $oldWarehouses->balance;
            echo json_encode($karat);
            exit;
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Karat  $karat
     * @return \Illuminate\Http\Response
     */
    public function edit(Karat $karat)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Karat  $karat
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $karat = Karat::find($request->id);
        if ($karat) {
            $validated = $request->validate([
                'name_ar' => ['required', Rule::unique('karats')->ignore($request->id)],
                'name_en' => ['required', Rule::unique('karats')->ignore($request->id)],
                'label' => ['required', Rule::unique('karats')->ignore($request->id)],
                'transform_factor' => ['required'],
            ]);

            try {
                $karat->update([
                    'name_ar' => $request->name_ar,
                    'name_en' => $request->name_en,
                    'label' => $request->label,
                    'stamp_value' => $request->stamp_value ?? 0,
                    'transform_factor' => $request->transform_factor ?? 1
                ]);
                return redirect()->route('karats')->with('success', __('main.updated'));
            } catch (QueryException $ex) {
                return redirect()->route('karats')->with('error', $ex->getMessage());
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Karat  $karat
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $karat = Karat::find($id);
        if ($karat) {
            $karat->delete();
            return redirect()->route('karats')->with('success', __('main.deleted'));
        }
    }
}
