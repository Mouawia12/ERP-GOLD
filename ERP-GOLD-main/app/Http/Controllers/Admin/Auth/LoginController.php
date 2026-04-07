<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Services\Branches\BranchContextService;
use App\Services\Auth\LoginModeService;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function __construct()
    {
        $this->middleware('guest:admin-web')->except('logout');
    }

    public function logout(Request $request)
    {
        app(LoginModeService::class)->clearAuthenticatedSession(
            Auth::guard('admin-web')->user(),
            $request->session()->getId()
        );
        app(BranchContextService::class)->clearSession($request->session());

        Auth::guard('admin-web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('admin.login')
            ->with('success', 'تم تسجيل الخروج بنجاح.');
    }

    protected function guard()
    {
        return Auth::guard('admin-web');
    }

    protected function credentials(Request $request)
    {
        return array_merge(
            $request->only($this->username(), 'password'),
            ['status' => true],
        );
    }

    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);
    }

    public function showLoginForm() {
        if (Auth::guard('admin-web')->check()) {
            return redirect()->route('admin.home');
        }else{
            return view('admin.auth.login');
        }
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);
 
        if (Auth::guard('admin-web')->attempt($credentials)) {
            $request->session()->regenerate(); 
            return redirect()->intended('home');
        }
 
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    protected function authenticated(Request $request, $user)
    {
        if ($user->subscriber && ! $user->subscriber->isActiveForLogin()) {
            app(LoginModeService::class)->clearAuthenticatedSession($user, $request->session()->getId());
            app(BranchContextService::class)->clearSession($request->session());
            Auth::guard('admin-web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('admin.login')
                ->withErrors([
                    'email' => 'هذا الاشتراك موقوف أو منتهي ولا يمكنه تسجيل الدخول حاليًا.',
                ]);
        }

        app(LoginModeService::class)->syncAuthenticatedSession($user, $request->session()->getId());
        app(BranchContextService::class)->applyToUser($user, $request->session());
    }
    
}
