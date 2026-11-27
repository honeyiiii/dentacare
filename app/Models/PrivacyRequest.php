<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivacyRequest extends Model
{
    protected $fillable = ['patient_id', 'request_type', 'message', 'status', 'handled_by', 'handled_at'];
    protected function casts(): array { return ['handled_at' => 'datetime']; }
    public function patient() { return $this->belongsTo(Patient::class)->withTrashed(); }
}
