<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'assigned_dentist_id',
        'avatar_path',
        'full_name',
        'date_of_birth',
        'sex',
        'contact_number',
        'address',
        'email',
    ];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function assignedDentist(): BelongsTo { return $this->belongsTo(User::class, 'assigned_dentist_id'); }
    public function appointments(): HasMany { return $this->hasMany(Appointment::class); }
    public function symptomChecks(): HasMany { return $this->hasMany(SymptomCheck::class); }
    public function privacyRequests(): HasMany { return $this->hasMany(PrivacyRequest::class); }
}
