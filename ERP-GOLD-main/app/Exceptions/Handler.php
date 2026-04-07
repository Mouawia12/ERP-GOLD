<?php

namespace App\Exceptions;

use App\Services\Auth\LoginModeService;
use App\Services\Branches\BranchContextService;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (TokenMismatchException $e, Request $request) {
            return $this->handleAdminAuthTokenMismatch($request);
        });
    }

    public function handleAdminAuthTokenMismatch(Request $request): ?RedirectResponse
    {
        $requestPath = trim($request->path(), '/');

        if (! Str::endsWith($requestPath, 'admin/login') && ! Str::endsWith($requestPath, 'admin/logout')) {
            return null;
        }

        $this->terminateAdminSession($request);

        return redirect()
            ->route('admin.login')
            ->with('error', 'انتهت الجلسة أو انتهت صلاحية الصفحة. يرجى تسجيل الدخول مرة أخرى.');
    }

    private function terminateAdminSession(Request $request): void
    {
        app(LoginModeService::class)->clearAuthenticatedSession(
            Auth::guard('admin-web')->user(),
            $request->hasSession() ? $request->session()->getId() : null,
        );

        if ($request->hasSession()) {
            app(BranchContextService::class)->clearSession($request->session());
        }

        Auth::guard('admin-web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }
}
