<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    protected $fillable = ['appointment_id', 'channel', 'status', 'scheduled_at', 'sent_at', 'last_error'];
    protected function casts(): array { return ['scheduled_at' => 'datetime', 'sent_at' => 'datetime']; }
    public function appointment() { return $this->belongsTo(Appointment::class); }
}
