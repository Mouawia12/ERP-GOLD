<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Response;
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

    protected function loggedOut(Request $request)
    {
        Auth::guard('admin-web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $request->wantsJson()
            ? new Response('', 204)
            : redirect('/');
    }

    protected function guard()
    {
        return Auth::guard('admin-web');
    }

    protected function credentials(Request $request)
    {
        return $request->only($this->username(), 'password');
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
            return view('admin.home');
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
    
}
