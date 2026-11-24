<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\RecordsClinicalAudit;
class Prediction extends Model
{
    use RecordsClinicalAudit;

    protected $fillable = ['patient_id', 'input_data', 'result', 'risk_scores', 'model_version_id', 'model_version', 'label', 'requested_by'];

    protected function casts(): array
    {
        return [
            'input_data' => 'encrypted:array',   // patient health data encrypted at rest
            'risk_scores' => 'array',
        ];
    }

    public function decisions() { return $this->hasMany(PredictionDecision::class); }
}
