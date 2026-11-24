<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class ClinicalRecordAudit extends Model
{
    public $timestamps = false;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Clinical audit records are immutable.'));
        static::deleting(fn () => throw new LogicException('Clinical audit records cannot be deleted.'));
    }

    protected $fillable = ['patient_id', 'user_id', 'record_type', 'record_id', 'event', 'changes', 'created_at'];

    protected function casts(): array
    {
        return [
            'changes' => 'encrypted:array',
            'created_at' => 'datetime',
        ];
    }

    public function user() { return $this->belongsTo(User::class); }
    public function patient() { return $this->belongsTo(Patient::class); }
}
