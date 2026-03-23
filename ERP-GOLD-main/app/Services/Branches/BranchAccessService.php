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
        return (bool) ($user?->is_admin ?? false);
    }

    public function branchIdForUser(User $user): ?int
    {
        return filled($user->branch_id) ? (int) $user->branch_id : null;
    }

    public function visibleBranchesQuery(User $user): Builder
    {
        return Branch::query()->when(
            ! $this->canAccessAllBranches($user),
            function (Builder $query) use ($user) {
                $branchId = $this->branchIdForUser($user);

                if ($branchId) {
                    $query->whereKey($branchId);
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

        $branchId = $this->branchIdForUser($user);

        if ($branchId) {
            return $query->where($column, $branchId);
        }

        return $query->whereRaw('1 = 0');
    }

    public function enforceBranchAccess(User $user, ?int $branchId): void
    {
        if ($this->canAccessAllBranches($user)) {
            return;
        }

        $allowedBranchId = $this->branchIdForUser($user);

        abort_unless(
            ! is_null($allowedBranchId) && (int) $allowedBranchId === (int) $branchId,
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
