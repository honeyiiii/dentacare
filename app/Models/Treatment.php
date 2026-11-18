<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Treatment extends Model
{
    protected $fillable = ['patient_id', 'tooth_chart_id', 'procedure', 'status', 'treatment_date', 'recorded_by'];
    protected function casts(): array { return ['treatment_date' => 'date']; }
    public function progressNotes() { return $this->hasMany(ProgressNote::class); }
}
