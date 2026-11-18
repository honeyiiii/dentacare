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

    public function medicalHistories() { return $this->hasMany(MedicalHistory::class); }
    public function dentalHistories() { return $this->hasMany(DentalHistory::class); }
    public function toothCharts() { return $this->hasMany(ToothChart::class); }
    public function diagnoses() { return $this->hasMany(Diagnosis::class); }
    public function treatments() { return $this->hasMany(Treatment::class); }
    public function predictions() { return $this->hasMany(Prediction::class); }
}
