<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static void created(\Closure $callback)
 * @method static void updated(\Closure $callback)
 * @method static void deleted(\Closure $callback)
 */
trait LogsActivity
{
    protected static function bootLogsActivity(): void
    {
        static::created(fn ($model) => ActivityLog::record(class_basename($model) . ' created', $model));

        static::updated(function ($model) {
            $changed = array_diff(array_keys($model->getChanges()), ['updated_at']);

            if ($changed) {
                ActivityLog::record(
                    class_basename($model) . ' updated',
                    $model,
                    'Changed: ' . implode(', ', $changed)
                );
            }
        });

        static::deleted(function ($model) {
            $soft = in_array(SoftDeletes::class, class_uses_recursive($model)) && ! $model->isForceDeleting();

            ActivityLog::record(class_basename($model) . ($soft ? ' archived' : ' deleted'), $model);
        });
    }
}