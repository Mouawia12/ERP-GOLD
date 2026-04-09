<?php

namespace App\Services\Reports;

use App\Models\Branch;
use App\Models\Subscriber;
use App\Models\User;
use App\Services\Branches\BranchContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReportBranchSelectionService
{
    public function __construct(
        private readonly BranchContextService $branchContextService,
    ) {
    }

    public function visibleBranches(?User $user, ?int $subscriberId = null): Collection
    {
        if (! $user) {
            return collect();
        }

        $resolvedSubscriberId = $this->resolvedSubscriberId($user, $subscriberId);
        $allowedBranchIds = $this->allowedBranchIds($user, $resolvedSubscriberId);

        return Branch::query()
            ->when(
                $resolvedSubscriberId,
                fn ($query) => $query->where('subscriber_id', $resolvedSubscriberId)
            )
            ->when($allowedBranchIds !== [], fn ($query) => $query->whereIn('id', $allowedBranchIds))
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function resolve(Request $request, ?User $user, ?int $subscriberId = null): array
    {
        $branches = $this->visibleBranches($user, $subscriberId)->values();
        $visibleBranchIds = $branches->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $selectedBranchIds = $this->normalizeSelectedBranchIds($request, $visibleBranchIds);

        if ($selectedBranchIds === [] && count($visibleBranchIds) === 1) {
            $selectedBranchIds = $visibleBranchIds;
        }

        $selectedBranches = $selectedBranchIds === []
            ? collect()
            : $branches->whereIn('id', $selectedBranchIds)->values();

        $selectsAll = $selectedBranchIds === []
            || count($selectedBranchIds) === count($visibleBranchIds);

        $effectiveBranchIds = $selectedBranchIds !== []
            ? $selectedBranchIds
            : $visibleBranchIds;

        $singleBranch = count($effectiveBranchIds) === 1
            ? $branches->firstWhere('id', $effectiveBranchIds[0])
            : null;

        return [
            'branches' => $branches,
            'visible_branch_ids' => $visibleBranchIds,
            'selected_branch_ids' => $selectedBranchIds,
            'effective_branch_ids' => $effectiveBranchIds,
            'single_branch' => $singleBranch,
            'branch_label' => $this->branchLabel($branches, $selectedBranches, $selectsAll),
            'selects_all' => $selectsAll,
            'legacy_branch_id' => count($selectedBranchIds) === 1 ? $selectedBranchIds[0] : ($singleBranch?->id ?: null),
        ];
    }

    /**
     * @param  array<int>  $visibleBranchIds
     * @return array<int>
     */
    public function normalizeSelectedBranchIds(Request $request, array $visibleBranchIds): array
    {
        $branchIds = collect($request->input('branch_ids', []));

        if ($request->filled('branch_id')) {
            $branchIds->push($request->input('branch_id'));
        }

        return $branchIds
            ->map(fn ($branchId) => (int) $branchId)
            ->filter()
            ->unique()
            ->filter(fn ($branchId) => in_array($branchId, $visibleBranchIds, true))
            ->values()
            ->all();
    }

    /**
     * @return array<int>
     */
    public function allowedBranchIds(?User $user, ?int $subscriberId = null): array
    {
        if (! $user) {
            return [];
        }

        $resolvedSubscriberId = $this->resolvedSubscriberId($user, $subscriberId);

        if ($resolvedSubscriberId && $this->isSubscriberPrimaryAccount($user, $resolvedSubscriberId)) {
            return Branch::query()
                ->where('subscriber_id', $resolvedSubscriberId)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        $accessibleBranchIds = $this->branchContextService->accessibleBranchIds($user);

        if ($resolvedSubscriberId) {
            return Branch::query()
                ->where('subscriber_id', $resolvedSubscriberId)
                ->when($accessibleBranchIds !== [], fn ($query) => $query->whereIn('id', $accessibleBranchIds))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        if ($accessibleBranchIds !== []) {
            return array_values(array_unique($accessibleBranchIds));
        }

        return Branch::query()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function resolvedSubscriberId(?User $user, ?int $subscriberId = null): ?int
    {
        if ($subscriberId) {
            return $subscriberId;
        }

        if (! $user || blank($user->subscriber_id)) {
            return null;
        }

        return (int) $user->subscriber_id;
    }

    private function isSubscriberPrimaryAccount(User $user, int $subscriberId): bool
    {
        if ((int) ($user->subscriber_id ?? 0) !== $subscriberId) {
            return false;
        }

        $subscriber = $user->relationLoaded('subscriber')
            ? $user->subscriber
            : Subscriber::query()->select('id', 'admin_user_id')->find($subscriberId);

        return (int) ($subscriber?->admin_user_id ?? 0) === (int) $user->id;
    }

    private function branchLabel(Collection $branches, Collection $selectedBranches, bool $selectsAll): string
    {
        if ($branches->count() === 1) {
            return (string) $branches->first()->name;
        }

        if ($selectsAll || $selectedBranches->isEmpty()) {
            return 'جميع الفروع';
        }

        return $selectedBranches->pluck('name')
            ->filter()
            ->implode('، ');
    }
}
