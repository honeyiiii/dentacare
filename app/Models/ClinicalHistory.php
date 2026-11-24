<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\RecordsClinicalAudit;
class ClinicalHistory extends Model
{
    use RecordsClinicalAudit;

    protected $fillable = [
        'patient_id', 'recorded_by', 'category', 'details', 'is_alert', 'alert_note',
        'flagged_by', 'flagged_at', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'encrypted',
            'alert_note' => 'encrypted',
            'is_alert' => 'boolean',
            'flagged_at' => 'datetime',
        ];
    }

    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
    public function flaggedBy(): BelongsTo { return $this->belongsTo(User::class, 'flagged_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
}
