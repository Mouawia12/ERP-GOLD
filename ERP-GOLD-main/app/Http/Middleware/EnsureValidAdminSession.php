<?php

namespace App\Http\Middleware;

use App\Services\Auth\LoginModeService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidAdminSession
{
    public function __construct(
        private readonly LoginModeService $loginModeService
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('admin-web')->user();

        if (!$user) {
            return $next($request);
        }

        if ($this->loginModeService->isRequestSessionValid($user, $request->session()->getId())) {
            return $next($request);
        }

        Auth::guard('admin-web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('admin.login')
            ->with('error', 'تم إنهاء الجلسة لأن الحساب تم تسجيل دخوله من جهاز آخر.');
    }
}
