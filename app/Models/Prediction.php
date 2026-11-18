<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Prediction extends Model
{
    protected $fillable = ['patient_id', 'input_data', 'result', 'risk_scores', 'model_version_id', 'model_version', 'label', 'requested_by'];
    protected function casts(): array { return ['input_data' => 'array', 'risk_scores' => 'array']; }
    public function decisions() { return $this->hasMany(PredictionDecision::class); }
}
