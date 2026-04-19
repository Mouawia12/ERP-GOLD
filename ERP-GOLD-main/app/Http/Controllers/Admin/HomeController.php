<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Branches\BranchContextService;
use App\Services\Dashboard\OwnerDashboardService;
use Illuminate\Http\Request;
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
    public function index(Request $request, BranchContextService $branchContextService, OwnerDashboardService $ownerDashboardService)
    {
        $user = Auth::user();
        $dashboardScope = $branchContextService->currentDashboardScope($user, $request->session());
        $dashboard = $ownerDashboardService->buildForUser(
            $user,
            $dashboardScope['branch_ids'],
            $dashboardScope['scope_label'],
            $dashboardScope['scope_mode_label'],
        );

        return view('admin.home', array_merge([
            'user' => $user,
            'dashboardBranchSelection' => $dashboardScope,
        ], $dashboard));
    }

    public function lock_screen()
    {
        return view('admin.lockscreen');
    }
}
