<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\RecordsClinicalAudit;
class Diagnosis extends Model
{
    use RecordsClinicalAudit;

    protected $fillable = ['patient_id', 'tooth_chart_id', 'dentist_id', 'recorded_by', 'diagnosis', 'notes'];

    protected function casts(): array
    {
        return ['diagnosis' => 'encrypted', 'notes' => 'encrypted'];
    }
}
