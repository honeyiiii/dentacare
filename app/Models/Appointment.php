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

    protected static function booted(): void
    {
        static::saving(function (Appointment $appointment): void {
            // Keep the unique database slot key canonical, and release it when the booking is no longer active.
            $appointment->slot_key = $appointment->status === 'Booked'
                ? $appointment->dentist_id . '|' . $appointment->appointment_date->toDateString() . '|' . substr($appointment->appointment_time, 0, 5)
                : null;
        });
    }

    public function patient(): BelongsTo { return $this->belongsTo(Patient::class); }
    public function dentist(): BelongsTo { return $this->belongsTo(User::class, 'dentist_id'); }
}
