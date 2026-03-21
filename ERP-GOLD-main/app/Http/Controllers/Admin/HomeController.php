<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\GoldPrice;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:admin-web');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable|\Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        $user = Auth::user();
        $roles = [];
        $users = [];
        $branches = [];
        $pricings = GoldPrice::all();
        $startDate = Carbon::now()->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $stock_market = '';

        if ($user->is_admin) {
            $users = User::all();
            $branches = Branch::all();
            $items = Item::count();

            $sales = Invoice::whereBetween('created_at', [$startDate, $endDate])->where('type', 'sale')->sum('net_total');

            $sales_return = Invoice::whereBetween('created_at', [$startDate, $endDate])->where('type', 'sale_return')->sum('net_total');

            $purchases = Invoice::whereBetween('created_at', [$startDate, $endDate])->where('type', 'purchase')->sum('net_total');

            $clients = Customer::where('type', 'customer')->get();
            $suppliers = Customer::where('type', 'supplier')->get();
        } else {
            $users = User::where('branch_id', $user->branch_id)->get();
            $branches = Branch::where('id', $user->branch_id)->get();
            $items = Item::where('branch_id', $user->branch_id)->count();

            $sales = Invoice::whereBetween('created_at', [$startDate, $endDate])->where('type', 'sale')->where('branch_id', $user->branch_id)->sum('net_total');

            $sales_return = Invoice::whereBetween('created_at', [$startDate, $endDate])->where('type', 'sale_return')->where('branch_id', $user->branch_id)->sum('net_total');

            $purchases = Invoice::whereBetween('created_at', [$startDate, $endDate])->where('type', 'purchase')->where('branch_id', $user->branch_id)->sum('net_total');

            $clients = Customer::where('type', 'customer')->get();
            $suppliers = Customer::where('type', 'supplier')->get();
        }

        return view('admin.home', compact('user', 'pricings', 'users', 'branches', 'sales', 'sales_return', 'purchases',
            'clients', 'suppliers', 'items', 'stock_market'));
    }

    public function lock_screen()
    {
        return view('admin.lockscreen');
    }
}
