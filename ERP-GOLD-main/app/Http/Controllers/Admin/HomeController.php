<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\OwnerDashboardService;
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
        $dashboard = app(OwnerDashboardService::class)->buildForUser($user);

        return view('admin.home', array_merge([
            'user' => $user,
        ], $dashboard));
    }

    public function lock_screen()
    {
        return view('admin.lockscreen');
    }
}
