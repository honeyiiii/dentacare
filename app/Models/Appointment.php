<?php

namespace App\Models;

use App\Models\Concerns\RecordsClinicalAudit;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use RecordsClinicalAudit;

    protected $fillable = [
        'patient_id',
        'dentist_id',
        'booked_by',
        'service',
        'appointment_date',
        'appointment_time',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'notes' => 'encrypted',
        ];
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function dentist()
    {
        return $this->belongsTo(User::class, 'dentist_id');
    }

    public function booker()
    {
        return $this->belongsTo(User::class, 'booked_by');
    }

    public function slotClaim()
    {
        return $this->hasOne(AppointmentSlotClaim::class);
    }
}
