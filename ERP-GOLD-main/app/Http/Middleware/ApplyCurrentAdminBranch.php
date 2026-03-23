<?php

namespace App\Http\Middleware;

use App\Services\Branches\BranchContextService;
use Closure;
use Illuminate\Http\Request;

class ApplyCurrentAdminBranch
{
    public function __construct(
        private readonly BranchContextService $branchContextService,
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user('admin-web');

        if ($user) {
            $currentBranch = $this->branchContextService->applyToUser($user, $request->session());

            view()->share('currentAdminBranch', $currentBranch);
            view()->share('availableAdminBranches', $this->branchContextService->accessibleBranches($user));
        }

        return $next($request);
    }
}
