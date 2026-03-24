<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $hasOwner = DB::table('users')
            ->where('is_admin', true)
            ->exists();

        if ($hasOwner) {
            return;
        }

        DB::table('users')
            ->where(function ($query) {
                $query->where('email', 'superadmin@gmail.com')
                    ->orWhere('name', 'Super Admin');
            })
            ->update([
                'is_admin' => true,
            ]);
    }

    public function down(): void
    {
        // Irreversible data correction.
    }
};
