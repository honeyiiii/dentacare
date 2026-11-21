<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\LogsActivity;

class Patient extends Model
{
use SoftDeletes, LogsActivity;
    protected $fillable = [
        'user_id',
        'full_name',
        'date_of_birth',
        'sex',
        'contact_number',
        'address',
        'email',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}