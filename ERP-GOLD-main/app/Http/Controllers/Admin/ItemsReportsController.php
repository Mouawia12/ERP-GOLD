<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\GoldCarat;
use App\Models\InvoiceDetail;
use App\Models\Item;
use App\Models\ItemCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use DB;

class ItemsReportsController extends Controller
{
    public function item_list_report()
    {
        $branches = Branch::all();
        $carats = GoldCarat::all();
        $categories = ItemCategory::all();
        return view('admin.reports.items.search', compact('branches', 'carats', 'categories'));
    }

    public function item_list_report_search(Request $request)
    {
        $items = Item::when($request->branch_id, function ($query) use ($request) {
            $query->where('branch_id', $request->branch_id);
        })
            ->when($request->carat, function ($query) use ($request) {
                $query->where('gold_carat_id', $request->carat);
            })
            ->when($request->category, function ($query) use ($request) {
                $query->where('category_id', $request->category);
            })
            ->when($request->code, function ($query) use ($request) {
                $query->where('code', $request->code);
            })
            ->when($request->name, function ($query) use ($request) {
                $query->where('title', 'like', '%' . $request->name . '%');
            })
            ->when($request->fcode || $request->tcode, function ($query) use ($request) {
                $query->whereBetween('code', [$request->fcode, $request->tcode]);
            })
            ->get();
        return view('admin.reports.items.index', compact('items'));
    }

    public function sold_items_report()
    {
        $branches = Branch::all();
        $carats = GoldCarat::all();
        $categories = ItemCategory::all();
        return view('admin.reports.sold_items.search', compact('branches', 'carats', 'categories'));
    }

    public function sold_items_report_search(Request $request)
    {
        $periodFrom = $request->date_from ? Carbon::parse($request->date_from)->format('Y-m-d') : Carbon::now()->startOfYear()->format('Y-m-d');
        $periodTo = $request->date_to ? Carbon::parse($request->date_to)->format('Y-m-d') : Carbon::now()->endOfYear()->format('Y-m-d');

        $itemsTransactions = InvoiceDetail::whereHas('invoice', function ($query) use ($request) {
            $query->where('type', 'sale')->when($request->branch_id, function ($query) use ($request) {
                $query->where('branch_id', $request->branch_id);
            });
        })
            ->when($request->category || $request->code || $request->name, function ($query) use ($request) {
                $query->whereHas('item', function ($query) use ($request) {
                    $query
                        ->when($request->category, function ($query) use ($request) {
                            $query->where('category_id', $request->category);
                        })
                        ->when($request->code, function ($query) use ($request) {
                            $query->where('code', $request->code);
                        })
                        ->when($request->name, function ($query) use ($request) {
                            $query->where('title', 'like', '%' . $request->name . '%');
                        });
                });
            })
            ->when($request->carat, function ($query) use ($request) {
                $query->where('gold_carat_id', $request->carat);
            })
            ->when($request->date_from || $request->date_to, function ($query) use ($request) {
                $query->whereBetween('date', [$request->date_from, $request->date_to]);
            })
            ->get();
        return view('admin.reports.sold_items.index', compact('itemsTransactions', 'periodFrom', 'periodTo'));
    }
}
