<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\GoldCarat;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Item;
use App\Models\ItemCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use DB;

class StockReportsController extends Controller
{
    public function sales_report_search()
    {
        $branches = Branch::where('status', 1)->get();
        return view('admin.reports.stock_reports.sales_report.search', compact('branches'));
    }

    public function sales_report(Request $request)
    {
        $periodFrom = $request->date_from ? Carbon::parse($request->date_from)->format('Y-m-d') : Carbon::now()->startOfYear()->format('Y-m-d');
        $periodTo = $request->date_to ? Carbon::parse($request->date_to)->format('Y-m-d') : Carbon::now()->endOfYear()->format('Y-m-d');
        $branch = $request->branch_id ? Branch::find($request->branch_id) : null;

        $details = InvoiceDetail::whereHas('invoice', function ($query) use ($request) {
            $query->where('type', 'sale')->when($request->branch_id, function ($query) use ($request) {
                $query->where('branch_id', $request->branch_id);
            });
        })
            ->when($request->date_from || $request->date_to, function ($query) use ($request) {
                $query->whereBetween('date', [$request->date_from, $request->date_to]);
            })
            ->get();
        return view('admin.reports.stock_reports.sales_report.index', compact('periodFrom', 'periodTo', 'branch', 'details'));
    }

    public function sales_total_report_search()
    {
        $branches = Branch::where('status', 1)->get();
        return view('admin.reports.stock_reports.sales_total_report.search', compact('branches'));
    }

    public function sales_total_report(Request $request)
    {
        $periodFrom = $request->date_from ? Carbon::parse($request->date_from)->format('Y-m-d') : Carbon::now()->startOfYear()->format('Y-m-d');
        $periodTo = $request->date_to ? Carbon::parse($request->date_to)->format('Y-m-d') : Carbon::now()->endOfYear()->format('Y-m-d');
        $branch = $request->branch_id ? Branch::find($request->branch_id) : null;

        $sales = Invoice::where('type', 'sale')
            ->when($request->branch_id, function ($query) use ($request) {
                $query->where('branch_id', $request->branch_id);
            })
            ->when($request->date_from || $request->date_to, function ($query) use ($request) {
                $query->whereBetween('date', [$request->date_from, $request->date_to]);
            })
            ->get();

        $sales_by_carat = InvoiceDetail::whereHas('invoice', function ($query) use ($request) {
            $query->where('type', 'sale')->when($request->branch_id, function ($query) use ($request) {
                $query->where('branch_id', $request->branch_id);
            });
        })
            ->when($request->date_from || $request->date_to, function ($query) use ($request) {
                $query->whereBetween('date', [$request->date_from, $request->date_to]);
            })
            ->join('gold_carats', 'invoice_details.gold_carat_id', '=', 'gold_carats.id')
            ->selectRaw('gold_carat_id , JSON_UNQUOTE(JSON_EXTRACT(gold_carats.title, "$.ar")) as carat_title, SUM(out_quantity) as total_quantity, SUM(out_weight) as total_weight, SUM(line_total) as total_line_total, SUM(line_tax) as total_taxes_total, SUM(net_total) as total_net_total')
            ->groupBy('gold_carat_id', 'gold_carats.title')
            ->get();
        return view('admin.reports.stock_reports.sales_total_report.index', compact('periodFrom', 'periodTo', 'branch', 'sales', 'sales_by_carat'));
    }

    public function sales_return_total_report_search()
    {
        $branches = Branch::where('status', 1)->get();
        return view('admin.reports.stock_reports.sales_return_total_report.search', compact('branches'));
    }

    public function sales_return_total_report(Request $request)
    {
        $periodFrom = $request->date_from ? Carbon::parse($request->date_from)->format('Y-m-d') : Carbon::now()->startOfYear()->format('Y-m-d');
        $periodTo = $request->date_to ? Carbon::parse($request->date_to)->format('Y-m-d') : Carbon::now()->endOfYear()->format('Y-m-d');
        $branch = $request->branch_id ? Branch::find($request->branch_id) : null;

        $sales_return = Invoice::where('type', 'sale_return')
            ->when($request->branch_id, function ($query) use ($request) {
                $query->where('branch_id', $request->branch_id);
            })
            ->when($request->date_from || $request->date_to, function ($query) use ($request) {
                $query->whereBetween('date', [$request->date_from, $request->date_to]);
            })
            ->get();

        $sales_return_by_carat = InvoiceDetail::whereHas('invoice', function ($query) use ($request) {
            $query->where('type', 'sale_return')->when($request->branch_id, function ($query) use ($request) {
                $query->where('branch_id', $request->branch_id);
            });
        })
            ->when($request->date_from || $request->date_to, function ($query) use ($request) {
                $query->whereBetween('date', [$request->date_from, $request->date_to]);
            })
            ->join('gold_carats', 'invoice_details.gold_carat_id', '=', 'gold_carats.id')
            ->selectRaw('gold_carat_id , JSON_UNQUOTE(JSON_EXTRACT(gold_carats.title, "$.ar")) as carat_title, SUM(in_quantity) as total_quantity, SUM(in_weight) as total_weight, SUM(line_total) as total_line_total, SUM(line_tax) as total_taxes_total, SUM(net_total) as total_net_total')
            ->groupBy('gold_carat_id', 'gold_carats.title')
            ->get();
        return view('admin.reports.stock_reports.sales_return_total_report.index', compact('periodFrom', 'periodTo', 'branch', 'sales_return', 'sales_return_by_carat'));
    }

    public function purchases_report_search()
    {
        $branches = Branch::where('status', 1)->get();
        return view('admin.reports.stock_reports.purchases_report.search', compact('branches'));
    }

    public function purchases_report(Request $request)
    {
        $periodFrom = $request->date_from ? Carbon::parse($request->date_from)->format('Y-m-d') : Carbon::now()->startOfYear()->format('Y-m-d');
        $periodTo = $request->date_to ? Carbon::parse($request->date_to)->format('Y-m-d') : Carbon::now()->endOfYear()->format('Y-m-d');
        $branch = $request->branch_id ? Branch::find($request->branch_id) : null;

        $details = InvoiceDetail::whereHas('invoice', function ($query) use ($request) {
            $query->where('type', 'purchase')->when($request->branch_id, function ($query) use ($request) {
                $query->where('branch_id', $request->branch_id);
            });
        })
            ->when($request->date_from || $request->date_to, function ($query) use ($request) {
                $query->whereBetween('date', [$request->date_from, $request->date_to]);
            })
            ->get();
        return view('admin.reports.stock_reports.purchases_report.index', compact('periodFrom', 'periodTo', 'branch', 'details'));
    }

    public function purchases_total_report_search()
    {
        $branches = Branch::where('status', 1)->get();
        return view('admin.reports.stock_reports.purchases_total_report.search', compact('branches'));
    }

    public function purchases_total_report(Request $request)
    {
        $periodFrom = $request->date_from ? Carbon::parse($request->date_from)->format('Y-m-d') : Carbon::now()->startOfYear()->format('Y-m-d');
        $periodTo = $request->date_to ? Carbon::parse($request->date_to)->format('Y-m-d') : Carbon::now()->endOfYear()->format('Y-m-d');
        $branch = $request->branch_id ? Branch::find($request->branch_id) : null;

        $purchases = Invoice::where('type', 'purchase')
            ->when($request->branch_id, function ($query) use ($request) {
                $query->where('branch_id', $request->branch_id);
            })
            ->when($request->date_from || $request->date_to, function ($query) use ($request) {
                $query->whereBetween('date', [$request->date_from, $request->date_to]);
            })
            ->get();
        return view('admin.reports.stock_reports.purchases_total_report.index', compact('periodFrom', 'periodTo', 'branch', 'purchases'));
    }
}
