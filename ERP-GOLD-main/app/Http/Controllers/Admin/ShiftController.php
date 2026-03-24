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

        $query = Shift::with(['branch', 'user'])->orderByDesc('opened_at');

        if (! $canManageShiftDirectory) {
            $query->where('user_id', $user->id);
        } else {
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
            'branches' => Branch::where('status', 1)->orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
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
        abort_unless($this->canManageShiftDirectory($user) || (int) $shift->user_id === (int) $user->id, 403);
    }

    private function canManageShiftDirectory(User $user): bool
    {
        return $user->canAny([
            'employee.users.show',
            'employee.user_permissions.show',
            'employee.branches.show',
        ]);
    }
}
