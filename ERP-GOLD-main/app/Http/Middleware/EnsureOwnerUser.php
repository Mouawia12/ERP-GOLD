<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOwnerUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('admin-web');

        abort_unless($user?->isOwner(), 403, 'هذه الصفحة مخصصة للمالك فقط.');

        return $next($request);
    }
}
