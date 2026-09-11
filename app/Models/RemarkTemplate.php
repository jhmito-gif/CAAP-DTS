<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RemarkTemplate extends Model
{
    protected $fillable = [
        'text',
    ];

    /**
     * The preset remark texts, ordered, for the remarks combobox.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    public static function options(): \Illuminate\Support\Collection
    {
        return static::orderBy('text')->pluck('text');
    }
}
