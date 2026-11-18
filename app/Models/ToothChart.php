<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ToothChart extends Model
{
    protected $fillable = ['patient_id', 'tooth_number', 'condition', 'recorded_by'];
    public function patient() { return $this->belongsTo(Patient::class); }
}
