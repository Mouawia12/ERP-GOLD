<?php

namespace App\Services\Branches;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

class BranchContextService
{
    public const SESSION_KEY = 'current_branch_id';

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
        if ($user->isOwner()) {
            return [];
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
        if ($user->isOwner()) {
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

        if ($user->isOwner()) {
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
        if (! $user->isOwner()) {
            abort_unless(
                in_array($branchId, $this->accessibleBranchIds($user), true),
                403,
                'لا يمكنك التبديل إلى فرع غير مخصص لك.'
            );
        }

        $session->put(self::SESSION_KEY, $branchId);
        $this->applyToUser($user, $session);
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
