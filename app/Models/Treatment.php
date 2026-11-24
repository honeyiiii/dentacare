<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\RecordsClinicalAudit;
class Treatment extends Model
{
    use RecordsClinicalAudit;

    protected $fillable = ['patient_id', 'tooth_chart_id', 'dentist_id', 'procedure', 'status', 'treatment_date', 'recorded_by'];
    protected function casts(): array { return ['procedure' => 'encrypted', 'treatment_date' => 'date']; }
    public function progressNotes() { return $this->hasMany(ProgressNote::class); }
    public function dentist() { return $this->belongsTo(User::class, 'dentist_id'); }
    public function recorder() { return $this->belongsTo(User::class, 'recorded_by'); }
}
