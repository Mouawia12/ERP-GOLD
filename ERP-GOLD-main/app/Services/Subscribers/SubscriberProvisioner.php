<?php

namespace App\Services\Subscribers;

use App\Models\AccountSetting;
use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Subscriber;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Accounts\SubscriberChartProvisioner;
use App\Services\Branches\BranchContextService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SubscriberProvisioner
{
    public function __construct(
        private readonly BranchContextService $branchContextService,
        private readonly SubscriberChartProvisioner $subscriberChartProvisioner,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload, User $owner): Subscriber
    {
        return DB::transaction(function () use ($payload, $owner) {
            $subscriber = Subscriber::create([
                'name' => $payload['name'],
                'code' => null,
                'login_email' => $payload['login_email'],
                'contact_email' => $payload['contact_email'] ?? null,
                'contact_phone' => $payload['contact_phone'] ?? null,
                'status' => (bool) ($payload['status'] ?? true),
                'is_trial' => (bool) ($payload['is_trial'] ?? false),
                'starts_at' => $payload['starts_at'] ?? null,
                'ends_at' => $payload['ends_at'] ?? null,
                'max_users' => $payload['max_users'] ?? 1,
                'max_branches' => $payload['max_branches'] ?? 1,
                'notes' => $payload['notes'] ?? null,
                'created_by_user_id' => $owner->id,
            ]);

            $subscriber->update([
                'code' => sprintf('SUB-%06d', $subscriber->id),
            ]);

            $branch = Branch::create([
                'subscriber_id' => $subscriber->id,
                'name' => [
                    'ar' => $payload['default_branch_name'] ?? 'الفرع الرئيسي',
                    'en' => $payload['default_branch_name'] ?? 'Main Branch',
                ],
                'email' => $payload['contact_email'] ?? $payload['login_email'],
                'phone' => $payload['contact_phone'] ?? null,
                'tax_number' => $payload['default_tax_number'] ?? null,
                'short_address' => $payload['default_address'] ?? null,
                'status' => true,
            ]);

            $adminUser = User::create([
                'subscriber_id' => $subscriber->id,
                'name' => $payload['admin_name'] ?? ('مدير ' . $payload['name']),
                'email' => $payload['login_email'],
                'password' => Hash::make($payload['password']),
                'branch_id' => $branch->id,
                'phone_number' => $payload['contact_phone'] ?? null,
                'profile_pic' => 'default.png',
                'status' => true,
                'is_admin' => false,
            ]);

            $role = $this->resolveSubscriberAdminRole();
            $role->syncPermissions($this->subscriberAdminPermissions());
            $adminUser->assignRole($role);
            $this->branchContextService->syncUserBranches($adminUser, [$branch->id], $branch->id);

            Warehouse::query()->create([
                'name' => 'المستودع الرئيسي',
                'code' => 'WH-' . $branch->id,
                'branch_id' => $branch->id,
            ]);

            $this->subscriberChartProvisioner->ensureBranchAccountSettings($subscriber, $branch);

            $subscriber->update([
                'admin_user_id' => $adminUser->id,
            ]);

            return $subscriber->fresh(['adminUser', 'branches']);
        });
    }

    private function resolveSubscriberAdminRole(): Role
    {
        $existingRole = Role::query()
            ->where('guard_name', 'admin-web')
            ->get()
            ->first(function (Role $role) {
                return in_array($role->name, [
                    'مدير حساب المشترك',
                    'Subscriber Admin',
                ], true);
            });

        if ($existingRole) {
            return $existingRole;
        }

        return Role::create([
            'name' => ['ar' => 'مدير حساب المشترك', 'en' => 'Subscriber Admin'],
            'guard_name' => 'admin-web',
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function subscriberAdminPermissions(): array
    {
        return Permission::query()
            ->where('guard_name', 'admin-web')
            ->where('name', 'not like', 'employee.subscribers.%')
            ->pluck('name')
            ->all();
    }
}
