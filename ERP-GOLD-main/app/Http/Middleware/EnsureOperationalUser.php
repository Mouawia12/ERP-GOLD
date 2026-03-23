<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOperationalUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('admin-web');

        if ($user?->isOwner()) {
            return redirect()
                ->route('admin.home')
                ->with('warning', 'لوحة المالك مخصصة لإدارة المشتركين فقط.');
        }

        return $next($request);
    }
}
