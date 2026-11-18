<?php
namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;

abstract class ClinicalHistoryController extends Controller
{
    protected string $modelClass;

    public function index(Patient $patient)
    {
        $model = $this->modelClass;
        return response()->json($model::where('patient_id', $patient->id)->latest()->paginate(20));
    }

    public function show(Patient $patient, int $record)
    {
        $model = $this->modelClass;
        $item = $model::where('patient_id', $patient->id)->findOrFail($record);

        return response()->json($item);
    }

    public function store(Request $request, Patient $patient)
    {
        $data = $request->validate(['details' => ['required', 'string'], 'is_alert' => ['sometimes', 'boolean'], 'alert_note' => ['nullable', 'string', 'max:1000']]);
        $alertWasProvided = array_key_exists('is_alert', $data) || array_key_exists('alert_note', $data);
        abort_if($alertWasProvided && $request->user()->role !== 'dentist', 403, 'Only a dentist may manage clinical alerts.');
        if (($data['is_alert'] ?? false) && blank($data['alert_note'] ?? null)) {
            return response()->json(['message' => 'An alert note is required when flagging an alert.'], 422);
        }
        $model = $this->modelClass;
        $record = $model::create($data + ['patient_id' => $patient->id, 'recorded_by' => $request->user()->id]);
        return response()->json($record, 201);
    }

    public function update(Request $request, Patient $patient, int $record)
    {
        $model = $this->modelClass;
        $item = $model::where('patient_id', $patient->id)->findOrFail($record);
        $data = $request->validate(['details' => ['sometimes', 'required', 'string'], 'is_alert' => ['sometimes', 'boolean'], 'alert_note' => ['nullable', 'string', 'max:1000']]);
        abort_if((array_key_exists('is_alert', $data) || array_key_exists('alert_note', $data)) && $request->user()->role !== 'dentist', 403, 'Only a dentist may manage clinical alerts.');
        $willBeAlert = (bool) ($data['is_alert'] ?? $item->is_alert);
        $alertNote = $data['alert_note'] ?? $item->alert_note;
        if ($willBeAlert && blank($alertNote)) {
            return response()->json(['message' => 'An alert note is required when an alert is active.'], 422);
        }
        $item->update($data);
        return response()->json($item->fresh());
    }
}
