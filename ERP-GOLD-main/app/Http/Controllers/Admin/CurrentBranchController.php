<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Branches\BranchContextService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CurrentBranchController extends Controller
{
    public function __construct(
        private readonly BranchContextService $branchContextService,
    ) {
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => [
                'required',
                'string',
                Rule::when(
                    $request->input('branch_id') !== BranchContextService::DASHBOARD_SCOPE_ALL,
                    ['exists:branches,id']
                ),
            ],
        ]);

        if ($validated['branch_id'] === BranchContextService::DASHBOARD_SCOPE_ALL) {
            $this->branchContextService->switchDashboardToAll(
                $request->user('admin-web'),
                $request->session()
            );

            return back()->with('success', 'تم تحديث نطاق الداشبورد إلى جميع الفروع بنجاح.');
        }

        $this->branchContextService->switchTo(
            $request->user('admin-web'),
            (int) $validated['branch_id'],
            $request->session()
        );

        return back()->with('success', 'تم تبديل الفرع النشط بنجاح.');
    }
}
