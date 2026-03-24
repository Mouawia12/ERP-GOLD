<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Services\Subscribers\SubscriberProvisioner;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscribersController extends Controller
{
    public function __construct(
        private readonly SubscriberProvisioner $subscriberProvisioner,
    ) {
        $this->middleware('permission:employee.subscribers.show,admin-web', ['only' => ['index', 'show']]);
        $this->middleware('permission:employee.subscribers.add,admin-web', ['only' => ['create', 'store']]);
        $this->middleware('permission:employee.subscribers.edit,admin-web', ['only' => ['edit', 'update']]);
        $this->middleware('permission:employee.subscribers.delete,admin-web', ['only' => ['destroy']]);
    }

    public function index()
    {
        $subscribers = Subscriber::query()
            ->with(['adminUser', 'branches'])
            ->withCount([
                'users as users_count',
                'branches as branches_count',
            ])
            ->latest()
            ->get();

        return view('admin.subscribers.index', compact('subscribers'));
    }

    public function create()
    {
        return view('admin.subscribers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'admin_name' => ['nullable', 'string', 'max:255'],
            'login_email' => ['required', 'email', 'max:255', 'unique:subscribers,login_email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'same:password_confirmation'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'max_branches' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'default_branch_name' => ['nullable', 'string', 'max:255'],
            'default_tax_number' => ['nullable', 'string', 'max:255'],
            'default_address' => ['nullable', 'string', 'max:500'],
            'is_trial' => ['nullable', 'boolean'],
            'status' => ['nullable', 'boolean'],
        ]);

        $subscriber = $this->subscriberProvisioner->create([
            ...$validated,
            'is_trial' => $request->boolean('is_trial'),
            'status' => $request->boolean('status', true),
            'default_branch_name' => $validated['default_branch_name'] ?? 'الفرع الرئيسي',
            'max_users' => $validated['max_users'] ?? 1,
            'max_branches' => $validated['max_branches'] ?? 1,
        ], $request->user('admin-web'));

        return redirect()
            ->route('admin.subscribers.show', $subscriber)
            ->with('success', 'تم إنشاء المشترك وحساب الدخول الأول بنجاح.');
    }

    public function show(Subscriber $subscriber)
    {
        $subscriber->load([
            'adminUser.branch',
            'branches' => fn ($query) => $query->withCount('activeAssignedUsers'),
            'users.branch',
        ]);

        return view('admin.subscribers.show', compact('subscriber'));
    }

    public function edit(Subscriber $subscriber)
    {
        return view('admin.subscribers.edit', compact('subscriber'));
    }

    public function update(Request $request, Subscriber $subscriber)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'login_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('subscribers', 'login_email')->ignore($subscriber->id),
                Rule::unique('users', 'email')->ignore($subscriber->admin_user_id),
            ],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'max_branches' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_trial' => ['nullable', 'boolean'],
            'status' => ['nullable', 'boolean'],
        ]);

        $subscriber->update([
            ...$validated,
            'is_trial' => $request->boolean('is_trial'),
            'status' => $request->boolean('status', true),
        ]);

        if ($subscriber->adminUser) {
            $subscriber->adminUser->update([
                'email' => $validated['login_email'],
                'phone_number' => $validated['contact_phone'] ?? $subscriber->adminUser->phone_number,
                'status' => (bool) $subscriber->status,
            ]);
        }

        return redirect()
            ->route('admin.subscribers.show', $subscriber)
            ->with('success', 'تم تحديث بيانات المشترك بنجاح.');
    }
}
