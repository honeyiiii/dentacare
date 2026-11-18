<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicalRecordAudit extends Model
{
    public $timestamps = false;

    protected $fillable = ['patient_id', 'user_id', 'record_type', 'record_id', 'event', 'changes', 'created_at'];

    protected function casts(): array
    {
        return [
            'changes' => 'encrypted:array',
            'created_at' => 'datetime',
        ];
    }
}
