<?php

namespace App\Services\Printing;

use Illuminate\Http\Request;

class PrintUrlBuilder
{
    /**
     * @param  array<string>  $except
     * @param  array<string, mixed>  $merge
     */
    public function routeFromRequest(string $routeName, Request $request, array $except = ['auto_print', 'embedded', 'pdf'], array $merge = []): string
    {
        $query = collect($request->query())
            ->except($except)
            ->merge($merge)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

        return route($routeName, $query);
    }
}
