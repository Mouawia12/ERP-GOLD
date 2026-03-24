<?php

namespace App\Services\Navigation;

use App\Models\User;

class AdminSidebarBuilder
{
    public function modeFor(?User $user): string
    {
        return $user?->isOwner() ? 'owner' : 'operational';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function ownerSections(?User $user): array
    {
        if (! $user?->isOwner()) {
            return [];
        }

        return $this->filterSections($user, [
            [
                'icon' => 'fa fa-users',
                'label' => 'إدارة المشتركين',
                'active_patterns' => [
                    'admin.subscribers.*',
                ],
                'items' => [
                    [
                        'label' => 'إضافة مشترك جديد',
                        'route' => 'admin.subscribers.create',
                        'permission' => 'employee.subscribers.add',
                        'active_patterns' => ['admin.subscribers.create'],
                    ],
                    [
                        'label' => 'قائمة المشتركين',
                        'route' => 'admin.subscribers.index',
                        'permission' => 'employee.subscribers.show',
                        'active_patterns' => ['admin.subscribers.index', 'admin.subscribers.show', 'admin.subscribers.edit'],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function operationalAdminSections(?User $user): array
    {
        if (! $user || $user->isOwner()) {
            return [];
        }

        return $this->filterSections($user, [
            [
                'icon' => 'fa fa-cogs',
                'label' => 'إعدادات الإدارة',
                'active_patterns' => [
                    'admin.system-settings.*',
                ],
                'items' => [
                    [
                        'label' => 'إعدادات تسجيل الدخول',
                        'route' => 'admin.system-settings.login-mode.edit',
                        'permission' => 'employee.system_settings.show',
                        'active_patterns' => ['admin.system-settings.login-mode.*'],
                    ],
                    [
                        'label' => 'شروط الفاتورة',
                        'route' => 'admin.system-settings.invoice-terms.edit',
                        'permission' => 'employee.system_settings.show',
                        'active_patterns' => ['admin.system-settings.invoice-terms.*'],
                    ],
                    [
                        'label' => 'إعدادات طباعة الفواتير',
                        'route' => 'admin.system-settings.invoice-print.edit',
                        'permission' => 'employee.system_settings.show',
                        'active_patterns' => ['admin.system-settings.invoice-print.*'],
                    ],
                    [
                        'label' => 'الشعار الرئيسي',
                        'route' => 'admin.system-settings.branding.edit',
                        'permission' => 'employee.system_settings.show',
                        'active_patterns' => ['admin.system-settings.branding.*'],
                    ],
                    [
                        'label' => 'الحسابات البنكية',
                        'route' => 'admin.system-settings.bank-accounts.index',
                        'permission' => 'employee.system_settings.show',
                        'active_patterns' => ['admin.system-settings.bank-accounts.*'],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     * @return array<int, array<string, mixed>>
     */
    private function filterSections(User $user, array $sections): array
    {
        return collect($sections)
            ->map(function (array $section) use ($user) {
                $items = collect($section['items'] ?? [])
                    ->filter(function (array $item) use ($user) {
                        $permission = $item['permission'] ?? null;

                        return $permission ? $user->can($permission) : true;
                    })
                    ->values()
                    ->all();

                $section['items'] = $items;

                return $section;
            })
            ->filter(fn (array $section) => ! empty($section['items']))
            ->values()
            ->all();
    }
}
