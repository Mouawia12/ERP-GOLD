<?php

namespace App\Services\Branches;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

class BranchContextService
{
    public const SESSION_KEY = 'current_branch_id';
    public const DASHBOARD_SESSION_KEY = 'dashboard_branch_scope';
    public const DASHBOARD_SCOPE_ALL = '__all__';

    public function accessibleBranches(User $user): Collection
    {
        if ($user->isOwner()) {
            return collect();
        }

        $branchIds = $this->accessibleBranchIds($user);

        if ($branchIds === []) {
            return collect();
        }

        return Branch::query()
            ->whereIn('id', $branchIds)
            ->latest()
            ->get();
    }

    /**
     * @return array<int>
     */
    public function accessibleBranchIds(User $user): array
    {
        if ($user->isOwner() && blank($user->subscriber_id)) {
            return [];
        }

        if (filled($user->subscriber_id) && $user->isOwner()) {
            return Branch::query()
                ->where('subscriber_id', $user->subscriber_id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        $branchIds = $user->branches()
            ->wherePivot('is_active', true)
            ->pluck('branches.id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $defaultBranchId = $this->persistedDefaultBranchId($user);

        if ($defaultBranchId && ! in_array($defaultBranchId, $branchIds, true)) {
            $branchIds[] = $defaultBranchId;
        }

        return array_values(array_unique(array_filter($branchIds)));
    }

    public function defaultBranchId(User $user): ?int
    {
        if ($user->isOwner() && blank($user->subscriber_id)) {
            return $this->persistedDefaultBranchId($user);
        }

        $pivotDefault = $user->branches()
            ->wherePivot('is_active', true)
            ->wherePivot('is_default', true)
            ->value('branches.id');

        if ($pivotDefault) {
            return (int) $pivotDefault;
        }

        $persistedDefault = $this->persistedDefaultBranchId($user);

        if ($persistedDefault) {
            return $persistedDefault;
        }

        return $this->accessibleBranchIds($user)[0] ?? null;
    }

    public function currentBranchId(User $user, Session $session): ?int
    {
        $defaultBranchId = $this->defaultBranchId($user);

        if ($user->isOwner() && blank($user->subscriber_id)) {
            return $defaultBranchId;
        }

        $allowedBranchIds = $this->accessibleBranchIds($user);
        $sessionBranchId = (int) $session->get(self::SESSION_KEY);

        if ($sessionBranchId && in_array($sessionBranchId, $allowedBranchIds, true)) {
            return $sessionBranchId;
        }

        return $defaultBranchId;
    }

    public function applyToUser(User $user, Session $session): ?Branch
    {
        $currentBranchId = $this->currentBranchId($user, $session);

        if (! $currentBranchId) {
            return null;
        }

        if ((int) $session->get(self::SESSION_KEY) !== $currentBranchId) {
            $session->put(self::SESSION_KEY, $currentBranchId);
        }

        $branch = Branch::find($currentBranchId);

        if ($branch) {
            $user->setAttribute('branch_id', $branch->id);
            $user->setRelation('branch', $branch);
        }

        return $branch;
    }

    public function switchTo(User $user, int $branchId, Session $session): void
    {
        if (! ($user->isOwner() && blank($user->subscriber_id))) {
            abort_unless(
                in_array($branchId, $this->accessibleBranchIds($user), true),
                403,
                'لا يمكنك التبديل إلى فرع غير مخصص لك.'
            );
        }

        $session->put(self::SESSION_KEY, $branchId);
        $session->put(self::DASHBOARD_SESSION_KEY, $branchId);
        $this->applyToUser($user, $session);
    }

    public function switchDashboardToAll(User $user, Session $session): void
    {
        if ($user->isOwner()) {
            $session->put(self::DASHBOARD_SESSION_KEY, self::DASHBOARD_SCOPE_ALL);

            return;
        }

        $allowedBranchIds = $this->accessibleBranchIds($user);

        abort_unless(
            count($allowedBranchIds) > 1,
            403,
            'لا يمكنك عرض جميع الفروع لأن حسابك لا يملك أكثر من فرع نشط.'
        );

        $session->put(self::DASHBOARD_SESSION_KEY, self::DASHBOARD_SCOPE_ALL);
    }

    /**
     * @return array{selected_value:int|string|null, branch_ids:array<int>|null, scope_label:string, scope_mode_label:string, uses_all_branches:bool}
     */
    public function currentDashboardScope(User $user, Session $session): array
    {
        if ($user->isOwner()) {
            return [
                'selected_value' => self::DASHBOARD_SCOPE_ALL,
                'branch_ids' => null,
                'scope_label' => 'جميع الفروع',
                'scope_mode_label' => 'عرض جميع الفروع',
                'uses_all_branches' => true,
            ];
        }

        $allowedBranchIds = $this->accessibleBranchIds($user);
        $storedScope = $session->get(self::DASHBOARD_SESSION_KEY);

        if (
            $storedScope === self::DASHBOARD_SCOPE_ALL
            && count($allowedBranchIds) > 1
        ) {
            return [
                'selected_value' => self::DASHBOARD_SCOPE_ALL,
                'branch_ids' => $allowedBranchIds,
                'scope_label' => 'جميع الفروع المسموح بها',
                'scope_mode_label' => 'عرض جميع الفروع المسموح بها',
                'uses_all_branches' => true,
            ];
        }

        $currentBranchId = $this->currentBranchId($user, $session);
        $selectedBranchId = is_numeric($storedScope) && in_array((int) $storedScope, $allowedBranchIds, true)
            ? (int) $storedScope
            : $currentBranchId;

        $selectedBranch = filled($selectedBranchId)
            ? Branch::query()->find($selectedBranchId)
            : null;

        return [
            'selected_value' => $selectedBranchId,
            'branch_ids' => filled($selectedBranchId) ? [(int) $selectedBranchId] : [],
            'scope_label' => $selectedBranch?->branch_name ?? 'بدون فرع',
            'scope_mode_label' => 'عرض الفرع النشط فقط',
            'uses_all_branches' => false,
        ];
    }

    /**
     * @param  array<int>  $branchIds
     */
    public function syncUserBranches(User $user, array $branchIds, ?int $defaultBranchId = null): void
    {
        $normalizedBranchIds = collect($branchIds)
            ->map(fn ($branchId) => (int) $branchId)
            ->filter()
            ->unique()
            ->values();

        if ($normalizedBranchIds->isEmpty() && $this->persistedDefaultBranchId($user)) {
            $normalizedBranchIds = collect([$this->persistedDefaultBranchId($user)]);
        }

        $resolvedDefaultBranchId = $defaultBranchId
            ? (int) $defaultBranchId
            : ($normalizedBranchIds->first() ?: null);

        if (
            $resolvedDefaultBranchId
            && ! $normalizedBranchIds->contains($resolvedDefaultBranchId)
        ) {
            $normalizedBranchIds->prepend($resolvedDefaultBranchId);
            $normalizedBranchIds = $normalizedBranchIds->unique()->values();
        }

        $syncPayload = [];

        foreach ($normalizedBranchIds as $branchId) {
            $syncPayload[$branchId] = [
                'is_default' => (int) $branchId === (int) $resolvedDefaultBranchId,
                'is_active' => true,
            ];
        }

        $user->branches()->sync($syncPayload);

        if ($resolvedDefaultBranchId) {
            $user->forceFill([
                'branch_id' => $resolvedDefaultBranchId,
            ])->save();
        }
    }

    public function clearSession(Session $session): void
    {
        $session->forget(self::SESSION_KEY);
        $session->forget(self::DASHBOARD_SESSION_KEY);
    }

    private function persistedDefaultBranchId(User $user): ?int
    {
        $rawBranchId = $user->getRawOriginal('branch_id');

        if (blank($rawBranchId) && ! blank($user->branch_id)) {
            $rawBranchId = $user->branch_id;
        }

        return blank($rawBranchId) ? null : (int) $rawBranchId;
    }
}
