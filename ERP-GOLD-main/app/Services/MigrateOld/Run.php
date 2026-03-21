<?php
namespace App\Services\MigrateOld;

class Run
{
    public static function run()
    {
        AccountsMove::move();
        // JournalsMove::move();
    }
}
