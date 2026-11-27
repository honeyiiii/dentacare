<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SymptomCheck extends Model
{
    protected $fillable = ['patient_id', 'symptoms', 'conditions', 'area', 'pain_level', 'duration', 'urgency', 'notes'];
    protected function casts(): array { return ['symptoms' => 'array', 'conditions' => 'array']; }
    public function patient() { return $this->belongsTo(Patient::class); }
}
