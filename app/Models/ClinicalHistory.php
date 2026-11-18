<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\RecordsClinicalAudit;
class ClinicalHistory extends Model
{
    use RecordsClinicalAudit;

    protected $fillable = ['patient_id', 'recorded_by', 'details', 'is_alert', 'alert_note'];
    protected function casts(): array { return ['is_alert' => 'boolean']; }
}
