<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `invoices` MODIFY `type` VARCHAR(255) NOT NULL DEFAULT 'sale'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE invoices ALTER COLUMN type TYPE VARCHAR(255)");
            DB::statement("ALTER TABLE invoices ALTER COLUMN type SET DEFAULT 'sale'");
        }

        foreach (['add', 'edit', 'delete', 'show'] as $action) {
            DB::table('permissions')->updateOrInsert(
                [
                    'name' => 'employee.manufacturing_orders.' . $action,
                    'guard_name' => 'admin-web',
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('guard_name', 'admin-web')
            ->whereIn('name', [
                'employee.manufacturing_orders.add',
                'employee.manufacturing_orders.edit',
                'employee.manufacturing_orders.delete',
                'employee.manufacturing_orders.show',
            ])
            ->delete();
    }
};
