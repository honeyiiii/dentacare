<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentSlotClaim extends Model
{
    protected $fillable = ['appointment_id', 'dentist_id', 'appointment_date', 'appointment_time'];
}
