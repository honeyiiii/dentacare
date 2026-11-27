<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $fillable = [
        'patient_id', 'dentist_id', 'service', 'appointment_date', 'appointment_time', 'status', 'slot_key', 'notes',
    ];

    protected function casts(): array
    {
        return ['appointment_date' => 'date'];
    }

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function dentist(): BelongsTo { return $this->belongsTo(User::class, 'dentist_id'); }
}
