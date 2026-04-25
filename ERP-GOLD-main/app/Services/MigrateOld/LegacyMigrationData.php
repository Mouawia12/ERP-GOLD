<?php

namespace App\Services\MigrateOld;

class LegacyMigrationData
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getArray(string $var): array
    {
        return require database_path('legacy/migrate_old/variables.php');
    }
}
