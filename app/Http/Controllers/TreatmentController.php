<?php
namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Treatment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TreatmentController extends Controller
{
    public function index(Patient $patient)
    {
        return response()->json(
            Treatment::with(['progressNotes', 'dentist:id,name'])->where('patient_id', $patient->id)->latest()->paginate(20)
        );
    }

    public function show(Patient $patient, Treatment $treatment)
    {
        abort_unless($treatment->patient_id === $patient->id, 404);

        return response()->json($treatment->load(['progressNotes.author:id,name', 'dentist:id,name']));
    }

    public function store(Request $request, Patient $patient)
    {
        $data = $request->validate($this->rules($patient));

        // the performing dentist defaults to the logged-in dentist
        if (empty($data['dentist_id']) && $request->user()->role === 'dentist') {
            $data['dentist_id'] = $request->user()->id;
        }

        return response()->json(Treatment::create($data + ['patient_id' => $patient->id, 'recorded_by' => $request->user()->id]), 201);
    }

    public function update(Request $request, Patient $patient, Treatment $treatment)
    {
        abort_unless($treatment->patient_id === $patient->id, 404);

        $treatment->update($request->validate($this->rules($patient, partial: true)));

        return response()->json($treatment->fresh());
    }

    private function rules(Patient $patient, bool $partial = false): array
    {
        return [
            // the tooth chart entry must belong to this patient
            'tooth_chart_id' => ['nullable', 'integer', Rule::exists('tooth_charts', 'id')->where('patient_id', $patient->id)],
            'dentist_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'dentist')],
            'procedure' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['planned', 'in_progress', 'completed', 'cancelled'])],
            'treatment_date' => ['nullable', 'date'],
        ];
    }
}
