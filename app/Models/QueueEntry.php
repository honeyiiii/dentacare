<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueEntry extends Model
{
    protected $fillable = [
        'appointment_id', 'patient_id', 'dentist_id', 'patient_name', 'service', 'queue_date', 'position', 'status', 'chair_key',
    ];

    protected function casts(): array { return ['queue_date' => 'date']; }

    public function appointment() { return $this->belongsTo(Appointment::class); }
    public function patient() { return $this->belongsTo(Patient::class); }
    public function dentist() { return $this->belongsTo(User::class, 'dentist_id'); }
}
