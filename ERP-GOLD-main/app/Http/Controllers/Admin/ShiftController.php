<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Shift;
use App\Models\User;
use App\Services\Shifts\ShiftService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ShiftController extends Controller
{
    public function __construct(
        private readonly ShiftService $shiftService,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user('admin-web');
        $canManageShiftDirectory = $this->canManageShiftDirectory($user);
        $subscriberId = $this->visibleSubscriberId($user);

        $query = Shift::with(['branch', 'user'])->orderByDesc('opened_at');

        if (! $canManageShiftDirectory) {
            $query->where('user_id', $user->id);
        } else {
            $this->scopeShiftQueryToSubscriber($query, $subscriberId);

            $query
                ->when($request->filled('branch_id'), function ($builder) use ($request) {
                    return $builder->where('branch_id', $request->integer('branch_id'));
                })
                ->when($request->filled('user_id'), function ($builder) use ($request) {
                    return $builder->where('user_id', $request->integer('user_id'));
                });
        }

        $query
            ->when($request->filled('status'), function ($builder) use ($request) {
                return $builder->where('status', $request->input('status'));
            })
            ->when($request->filled('date_from'), function ($builder) use ($request) {
                return $builder->whereDate('opened_at', '>=', $request->input('date_from'));
            })
            ->when($request->filled('date_to'), function ($builder) use ($request) {
                return $builder->whereDate('opened_at', '<=', $request->input('date_to'));
            });

        return view('admin.shifts.index', [
            'shifts' => $query->get(),
            'activeShift' => $this->shiftService->currentForUser($user),
            'branches' => $this->visibleBranches($subscriberId),
            'users' => $this->visibleUsers($subscriberId),
            'filters' => $request->only(['status', 'date_from', 'date_to', 'branch_id', 'user_id']),
            'canManageShiftDirectory' => $canManageShiftDirectory,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user('admin-web');

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'opening_cash' => 'nullable|numeric|min:0',
            'opening_notes' => 'nullable|string|max:2000',
        ]);

        $this->ensureBranchBelongsToVisibleSubscriber($user, (int) $validated['branch_id']);

        try {
            $this->shiftService->open(
                $user,
                (int) $validated['branch_id'],
                (float) ($validated['opening_cash'] ?? 0),
                $validated['opening_notes'] ?? null
            );
        } catch (ValidationException $exception) {
            return redirect()
                ->back()
                ->withErrors($exception->errors())
                ->withInput();
        }

        return redirect()
            ->route('admin.shifts.index')
            ->with('success', 'تم فتح الشفت بنجاح.');
    }

    public function close(Request $request, Shift $shift)
    {
        $user = $request->user('admin-web');
        $this->ensureVisibleTo($user, $shift);

        $validated = $request->validate([
            'closing_cash' => 'required|numeric|min:0',
            'closing_notes' => 'nullable|string|max:2000',
        ]);

        try {
            $this->shiftService->close(
                $shift,
                (float) $validated['closing_cash'],
                $validated['closing_notes'] ?? null
            );
        } catch (ValidationException $exception) {
            return redirect()
                ->back()
                ->withErrors($exception->errors())
                ->withInput();
        }

        return redirect()
            ->route('admin.shifts.show', $shift)
            ->with('success', 'تم إغلاق الشفت بنجاح.');
    }

    public function show(Request $request, Shift $shift)
    {
        $this->ensureVisibleTo($request->user('admin-web'), $shift);

        return view('admin.shifts.show', [
            'shift' => $shift->load(['branch', 'user']),
            'summary' => $this->shiftService->summary($shift),
        ]);
    }

    private function ensureVisibleTo($user, Shift $shift): void
    {
        if ((int) $shift->user_id === (int) $user->id) {
            return;
        }

        abort_unless($this->canManageShiftDirectory($user), 403);

        $subscriberId = $this->visibleSubscriberId($user);

        if (! $subscriberId) {
            return;
        }

        $shift->loadMissing(['branch', 'user']);

        $belongsToVisibleSubscriber = (int) ($shift->branch?->subscriber_id ?? 0) === $subscriberId
            || (int) ($shift->user?->subscriber_id ?? 0) === $subscriberId;

        abort_unless($belongsToVisibleSubscriber, 403);
    }

    private function canManageShiftDirectory(User $user): bool
    {
        return $user->canAny([
            'employee.users.show',
            'employee.user_permissions.show',
            'employee.branches.show',
        ]);
    }

    private function visibleSubscriberId(User $user): ?int
    {
        if (blank($user->subscriber_id)) {
            return null;
        }

        return (int) $user->subscriber_id;
    }

    private function scopeShiftQueryToSubscriber($query, ?int $subscriberId): void
    {
        if (! $subscriberId) {
            return;
        }

        $query->where(function ($builder) use ($subscriberId) {
            $builder
                ->whereHas('branch', function ($branchQuery) use ($subscriberId) {
                    $branchQuery->where('subscriber_id', $subscriberId);
                })
                ->orWhereHas('user', function ($userQuery) use ($subscriberId) {
                    $userQuery->where('subscriber_id', $subscriberId);
                });
        });
    }

    private function visibleBranches(?int $subscriberId)
    {
        return Branch::query()
            ->where('status', 1)
            ->when(
                $subscriberId,
                fn ($query) => $query->where('subscriber_id', $subscriberId)
            )
            ->orderBy('name')
            ->get();
    }

    private function visibleUsers(?int $subscriberId)
    {
        return User::query()
            ->when(
                $subscriberId,
                fn ($query) => $query->where('subscriber_id', $subscriberId)
            )
            ->orderBy('name')
            ->get();
    }

    private function ensureBranchBelongsToVisibleSubscriber(User $user, int $branchId): void
    {
        $subscriberId = $this->visibleSubscriberId($user);

        if (! $subscriberId) {
            return;
        }

        $branchBelongsToSubscriber = Branch::query()
            ->whereKey($branchId)
            ->where('subscriber_id', $subscriberId)
            ->exists();

        if ($branchBelongsToSubscriber) {
            return;
        }

        throw ValidationException::withMessages([
            'branch_id' => ['لا يمكنك فتح شفت على فرع تابع لمشترك آخر.'],
        ]);
    }
}
