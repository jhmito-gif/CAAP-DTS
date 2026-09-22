<?php

namespace App\Models;

use App\Support\Modules;
use Illuminate\Database\Eloquent\Model;

/**
 * Whether one optional module is switched on (see App\Support\Modules).
 */
class ModuleSetting extends Model
{
    protected $fillable = [
        'module',
        'enabled',
        'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Every page asks which modules are on, so the answer is cached; a
        // change must be seen at once, everywhere.
        static::saved(fn () => Modules::forget());
        static::deleted(fn () => Modules::forget());
    }
}
