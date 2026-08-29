<?php

namespace App\Services\Permissions;

class PermissionMatrixService
{
    /**
     * @var array<string, string>
     */
    private array $permissionActions = [
        'add' => 'اضافة',
        'show' => 'عرض',
        'edit' => 'تعديل',
        'delete' => 'حذف',
    ];

    /**
     * وحدات لها إجراءات أقل من الأربعة الافتراضية — «تقارير كل المستخدمين»
     * مفتاح رؤية لا يُضاف ولا يُعدَّل ولا يُحذف، فيكفيه «عرض».
     *
     * @var array<string, array<int, string>>
     */
    private array $moduleActions = [
        'all_users_reports' => ['show'],
    ];

    /**
     * @return array<string, array<string, string>>
     */
    private function actionsFor(string $module): array
    {
        $allowed = $this->moduleActions[$module] ?? array_keys($this->permissionActions);

        return collect($this->permissionActions)
            ->only($allowed)
            ->mapWithKeys(fn ($actionLabel, $actionKey) => [
                $actionKey => [
                    'label' => $actionLabel,
                    'name' => "employee.{$module}.{$actionKey}",
                ],
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function permissionGroups(): array
    {
        $categories = [
            'الادارة' => ['users', 'user_permissions', 'branches', 'system_settings'],
            'المبيعات' => ['tax_invoices', 'simplified_tax_invoices', 'sales_returns', 'receipt_vouchers', 'cash_in_entries'],
            'المشتريات' => ['purchase_invoices', 'expense_vouchers', 'cash_out_entries'],
            'المخزون والتشغيل' => ['items', 'initial_quantities', 'stock_entries', 'warehouses', 'stock', 'inventory_list', 'stock_settlements', 'manufacturing_orders', 'workbook', 'breakbook', 'convert_work_to_break', 'branch_karat_transfers'],
            'العملاء والموردون' => ['customers', 'suppliers'],
            'المحاسبة' => ['accounts', 'journal_entries'],
            'التقارير' => ['inventory_reports', 'accounting_reports', 'gold_balance_sheet', 'all_users_reports'],
            'الاسعار' => ['gold_prices'],
        ];

        $configuredModules = collect(config('settings.permissions_modules'))
            ->map(fn ($module) => (string) $module);

        $grouped = [];
        foreach ($categories as $categoryLabel => $modules) {
            $categoryModules = $configuredModules
                ->filter(fn ($module) => in_array($module, $modules, true))
                ->values();

            if ($categoryModules->isEmpty()) {
                continue;
            }

            $grouped[] = [
                'label' => $categoryLabel,
                'modules' => $categoryModules->map(fn ($module) => [
                    'key' => $module,
                    'label' => __("dashboard.permissions_modules.$module"),
                    'permissions' => $this->actionsFor($module),
                ])->all(),
            ];
        }

        $alreadyGrouped = collect($grouped)
            ->flatMap(fn ($group) => collect($group['modules'])->pluck('key'))
            ->all();

        $remainingModules = $configuredModules
            ->reject(fn ($module) => in_array($module, $alreadyGrouped, true))
            ->values();

        if ($remainingModules->isNotEmpty()) {
            $grouped[] = [
                'label' => 'اخرى',
                'modules' => $remainingModules->map(fn ($module) => [
                    'key' => $module,
                    'label' => __("dashboard.permissions_modules.$module"),
                    'permissions' => $this->actionsFor($module),
                ])->all(),
            ];
        }

        return $grouped;
    }
}
