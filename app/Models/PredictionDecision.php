<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\RecordsClinicalAudit;
class PredictionDecision extends Model
{
    use RecordsClinicalAudit;

    protected $fillable = ['prediction_id', 'dentist_id', 'decision', 'final_condition', 'note'];

    protected function casts(): array
    {
        return ['final_condition' => 'encrypted', 'note' => 'encrypted'];
    }

    public function prediction() { return $this->belongsTo(Prediction::class); }
}
