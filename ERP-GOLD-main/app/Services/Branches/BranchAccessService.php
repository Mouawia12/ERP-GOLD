<?php

namespace App\Services\Branches;

use App\Models\Branch;
use App\Models\FinancialVoucher;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class BranchAccessService
{
    public function canAccessAllBranches(?User $user): bool
    {
        return (bool) $user?->isOwner();
    }

    public function branchIdForUser(User $user): ?int
    {
        $sessionBranchId = (int) session(BranchContextService::SESSION_KEY);

        if (
            $sessionBranchId
            && in_array($sessionBranchId, app(BranchContextService::class)->accessibleBranchIds($user), true)
        ) {
            return $sessionBranchId;
        }

        return filled($user->branch_id) ? (int) $user->branch_id : null;
    }

    /**
     * كل الفروع المسموح بها للمستخدم (وليس فقط الفرع الحالي في الجلسة).
     *
     * @return array<int>
     */
    public function accessibleBranchIdsForUser(User $user): array
    {
        $branchIds = app(BranchContextService::class)->accessibleBranchIds($user);

        if ($branchIds === [] && filled($user->branch_id)) {
            $branchIds = [(int) $user->branch_id];
        }

        return $branchIds;
    }

    public function visibleBranchesQuery(User $user): Builder
    {
        return Branch::query()->when(
            ! $this->canAccessAllBranches($user),
            function (Builder $query) use ($user) {
                $branchIds = $this->accessibleBranchIdsForUser($user);

                if ($branchIds !== []) {
                    $query->whereIn('id', $branchIds);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }
        );
    }

    public function visibleBranches(User $user)
    {
        return $this->visibleBranchesQuery($user)->latest()->get();
    }

    public function scopeToAccessibleBranch(Builder $query, User $user, string $column = 'branch_id'): Builder
    {
        if ($this->canAccessAllBranches($user)) {
            return $query;
        }

        $branchIds = $this->accessibleBranchIdsForUser($user);

        if ($branchIds !== []) {
            return $query->whereIn($column, $branchIds);
        }

        return $query->whereRaw('1 = 0');
    }

    public function enforceBranchAccess(User $user, ?int $branchId): void
    {
        if ($this->canAccessAllBranches($user)) {
            return;
        }

        $allowedBranchIds = $this->accessibleBranchIdsForUser($user);

        abort_unless(
            $allowedBranchIds !== [] && in_array((int) $branchId, $allowedBranchIds, true),
            403,
            'لا يمكنك الوصول إلى بيانات فرع غير مخصص لك.'
        );
    }

    public function enforceInvoiceAccess(User $user, Invoice $invoice): void
    {
        $this->enforceBranchAccess($user, (int) $invoice->branch_id);
    }

    public function enforceVoucherAccess(User $user, FinancialVoucher $voucher): void
    {
        $this->enforceBranchAccess($user, (int) $voucher->branch_id);
    }
}
