<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SymptomCheck extends Model
{
    protected $fillable = [
        'user_id', 'patient_id', 'area', 'symptoms', 'pain_level',
        'duration', 'notes', 'conditions', 'urgency', 'recommended_window',
    ];

    protected $casts = [
        'symptoms'   => 'array',
        'conditions' => 'array',
    ];
}   