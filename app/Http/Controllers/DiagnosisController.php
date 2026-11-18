<?php
namespace App\Http\Controllers;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\ToothChart;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class DiagnosisController extends Controller
{
    public function index(Patient $patient) { return response()->json(Diagnosis::where('patient_id',$patient->id)->latest()->paginate(20)); }
    public function show(Patient $patient, Diagnosis $diagnosis) { abort_unless($diagnosis->patient_id === $patient->id, 404); return response()->json($diagnosis); }
    public function store(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'tooth_chart_id' => ['nullable', 'integer', Rule::exists('tooth_charts', 'id')->where('patient_id', $patient->id)],
            'diagnosis' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'dentist_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'dentist')],
        ]);

        $dentistId = $data['dentist_id'] ?? ($request->user()->role === 'dentist' ? $request->user()->id : null);
        if (! $dentistId) {
            return response()->json(['message' => 'Select the dentist who made this diagnosis.', 'errors' => ['dentist_id' => ['A dentist is required.']]], 422);
        }

        $diagnosis = Diagnosis::create($data + [
            'patient_id' => $patient->id,
            'dentist_id' => $dentistId,
            'recorded_by' => $request->user()->id,
        ]);

        return response()->json($diagnosis, 201);
    }

    public function update(Request $request, Patient $patient, Diagnosis $diagnosis)
    {
        abort_unless($diagnosis->patient_id === $patient->id, 404);
        $data = $request->validate([
            'diagnosis' => ['sometimes', 'required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'dentist_id' => ['sometimes', 'required', 'integer', Rule::exists('users', 'id')->where('role', 'dentist')],
        ]);
        $diagnosis->update($data);

        return response()->json($diagnosis->fresh());
    }
}
