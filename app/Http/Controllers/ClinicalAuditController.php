<?php

namespace App\Http\Controllers;

use App\Models\ClinicalRecordAudit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClinicalAuditController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'patient_id' => ['nullable', 'integer', Rule::exists('patients', 'id')],
            'record_type' => ['nullable', 'string', 'max:100'],
            'event' => ['nullable', Rule::in(['created', 'updated', 'deleted', 'archived'])],
        ]);

        $logs = ClinicalRecordAudit::with(['user:id,name', 'patient:id,full_name'])
            ->when($filters['patient_id'] ?? null, fn ($query, $patientId) => $query->where('patient_id', $patientId))
            ->when($filters['record_type'] ?? null, fn ($query, $recordType) => $query->where('record_type', $recordType))
            ->when($filters['event'] ?? null, fn ($query, $event) => $query->where('event', $event))
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();

        return response()->json($logs);
    }
}
