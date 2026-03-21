<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CompanyInfo;
use App\Models\GoldCarat;
use App\Models\GoldCaratType;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WarehouseController extends Controller
{
    public function gold_stock(Request $request)
    {
        $caratsTypes = GoldCaratType::all();
        $carats = GoldCarat::all();

        $baseCarat = GoldCarat::where('transform_factor', 1)->first();

        $company = null;

        $periodFrom = $request->date_from ? Carbon::parse($request->date_from)->format('Y-m-d') : Carbon::now()->startOfYear()->format('Y-m-d');
        $periodTo = $request->date_to ? Carbon::parse($request->date_to)->format('Y-m-d') : Carbon::now()->endOfYear()->format('Y-m-d');

        return view('admin.reports.stock_reports.gold_stock.index', compact('caratsTypes', 'carats', 'baseCarat', 'company', 'periodFrom', 'periodTo'));
    }

    public function gold_stock_search()
    {
        $branches = Branch::where('status', 1)->get();
        return view('admin.reports.stock_reports.gold_stock.search', compact('branches'));
    }
}
