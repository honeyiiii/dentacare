<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\RecordsClinicalAudit;
class ProgressNote extends Model
{
    use RecordsClinicalAudit;

    protected $fillable = ['treatment_id', 'note', 'author_id'];
    protected function casts(): array { return ['note' => 'encrypted']; }

    // treatment() lets the audit trait find the patient for each note
    public function treatment() { return $this->belongsTo(Treatment::class); }
    public function author() { return $this->belongsTo(User::class, 'author_id'); }
}
