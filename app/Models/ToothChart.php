<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\RecordsClinicalAudit;
class ToothChart extends Model
{
    use RecordsClinicalAudit;

    public const CONDITIONS = [
        'healthy', 'caries', 'filled', 'crowned', 'missing', 'root_canal',
        'fractured', 'impacted', 'extraction_needed', 'periodontal_issue', 'other',
    ];

    protected $fillable = ['patient_id', 'tooth_number', 'condition', 'recorded_by'];
    public function patient() { return $this->belongsTo(Patient::class); }
    public function recorder() { return $this->belongsTo(User::class, 'recorded_by'); }
}
