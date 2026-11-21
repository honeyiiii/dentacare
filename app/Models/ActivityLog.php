<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'ip_address',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Audit logs cannot be edited.'));
        static::deleting(fn () => throw new \LogicException('Audit logs cannot be deleted.'));
    }

    public static function record(string $action,$subject = null, ?string $description = null, ?int $userId = null): void
    {
        self::create([
             'user_id'      => $userId ?? Auth::id(),
            'action'       => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->getKey(),
            'description'  => $description,
            'ip_address'   => request()->ip(),
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}