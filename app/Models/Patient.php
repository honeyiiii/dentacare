<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'full_name',
        'date_of_birth',
        'sex',
        'contact_number',
        'address',
        'email',
    ];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date'];
    }
}
