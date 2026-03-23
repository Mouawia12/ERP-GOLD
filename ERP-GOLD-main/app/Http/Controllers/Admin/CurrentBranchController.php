<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Branches\BranchContextService;
use Illuminate\Http\Request;

class CurrentBranchController extends Controller
{
    public function __construct(
        private readonly BranchContextService $branchContextService,
    ) {
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
        ]);

        $this->branchContextService->switchTo(
            $request->user('admin-web'),
            (int) $validated['branch_id'],
            $request->session()
        );

        return back()->with('success', 'تم تبديل الفرع النشط بنجاح.');
    }
}
