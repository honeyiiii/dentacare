<?php
namespace App\Http\Controllers;

use App\Models\MedicalHistory;
use App\Models\Patient;

class MedicalHistoryController extends ClinicalHistoryController
{
    protected string $modelClass = MedicalHistory::class;
    protected bool $supportsAlerts = true;

    protected function categories(): array
    {
        return ['allergy', 'cardiac', 'medication', 'chronic_condition', 'surgery', 'pregnancy', 'other'];
    }

    /** GET /api/patients/{patient}/medical-alerts: flagged items only, for the warning banner on the record. */
    public function alerts(Patient $patient)
    {
        return response()->json([
            'data' => MedicalHistory::where('patient_id', $patient->id)
                ->where('is_alert', true)
                ->with('flaggedBy:id,name')
                ->latest('flagged_at')
                ->get(),
        ]);
    }
}
