<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use Concerns\Audited;
    protected $fillable = [
        'name',
    ];
}
