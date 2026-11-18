<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\RecordsClinicalAudit;
class ToothChart extends Model
{
    use RecordsClinicalAudit;

    protected $fillable = ['patient_id', 'tooth_number', 'condition', 'recorded_by'];
    public function patient() { return $this->belongsTo(Patient::class); }
}
