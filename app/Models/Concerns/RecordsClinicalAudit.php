<?php

namespace App\Models\Concerns;

use App\Models\ClinicalRecordAudit;
use Illuminate\Database\Eloquent\Model;

trait RecordsClinicalAudit
{
    protected static function bootRecordsClinicalAudit(): void
    {
        static::created(function (Model $record): void {
            static::writeAudit($record, 'created', [], $record->getAttributes());
        });

        static::updating(function (Model $record): void {
            $after = $record->getDirty();
            unset($after['updated_at']);

            if ($after !== []) {
                $before = [];
                foreach (array_keys($after) as $attribute) {
                    $before[$attribute] = $record->getOriginal($attribute);
                }
                static::writeAudit($record, 'updated', $before, $after);
            }
        });

        static::deleting(function (Model $record): void {
            static::writeAudit($record, 'deleted', $record->getAttributes(), []);
        });
    }

    private static function writeAudit(Model $record, string $event, array $before, array $after): void
    {
        $patientId = $record->getAttribute('patient_id');
        if (! $patientId && method_exists($record, 'treatment')) {
            $patientId = $record->treatment?->patient_id;
        }
        if (! $patientId && method_exists($record, 'prediction')) {
            $patientId = $record->prediction?->patient_id;
        }

        ClinicalRecordAudit::create([
            'patient_id' => $patientId,
            'user_id' => auth()->id(),
            'record_type' => $record->getTable(),
            'record_id' => $record->getKey(),
            'event' => $event,
            'changes' => ['before' => $before, 'after' => $after],
            'created_at' => now(),
        ]);
    }
}
