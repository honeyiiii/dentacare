<?php
namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\ToothChart;
use App\Support\Fdi;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ToothChartController extends Controller
{
    public function index(Request $request, Patient $patient)
    {
        $q = ToothChart::where('patient_id', $patient->id)->with('recorder:id,name');
        if ($request->filled('tooth_number')) {
            $q->where('tooth_number', (int) $request->query('tooth_number'));
        }

        return response()->json($q->latest()->paginate(50));
    }

    public function show(Patient $patient, ToothChart $toothChart)
    {
        abort_unless($toothChart->patient_id === $patient->id, 404);

        return response()->json($toothChart->load('recorder:id,name'));
    }

    public function store(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'tooth_number' => ['required', 'integer', Rule::in(Fdi::teeth())],   // FDI: permanent 11-48, primary 51-85
            'condition' => ['required', Rule::in(ToothChart::CONDITIONS)],
        ]);

        return response()->json(ToothChart::create($data + ['patient_id' => $patient->id, 'recorded_by' => $request->user()->id]), 201);
    }

    public function update(Request $request, Patient $patient, ToothChart $toothChart)
    {
        abort_unless($toothChart->patient_id === $patient->id, 404);

        $data = $request->validate([
            'tooth_number' => ['sometimes', 'integer', Rule::in(Fdi::teeth())],
            'condition' => ['sometimes', Rule::in(ToothChart::CONDITIONS)],
        ]);
        $toothChart->update($data);

        return response()->json($toothChart->fresh());
    }
}
