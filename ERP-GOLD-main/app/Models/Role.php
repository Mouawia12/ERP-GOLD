<?php
namespace App\Models;

use App\Traits\HasTranslations;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasTranslations;

    public $translatable = ['name'];
}
