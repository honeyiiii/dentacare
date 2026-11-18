<?php
namespace App\Http\Controllers;
use App\Models\Patient;
use App\Models\ToothChart;
use Illuminate\Http\Request;
class ToothChartController extends Controller
{
    public function index(Patient $patient) { return response()->json(ToothChart::where('patient_id', $patient->id)->latest()->paginate(50)); }
    public function store(Request $request, Patient $patient) { $data = $request->validate(['tooth_number' => ['required','integer','between:1,32'], 'condition' => ['required','string','max:255']]); return response()->json(ToothChart::create($data + ['patient_id'=>$patient->id,'recorded_by'=>$request->user()->id]), 201); }
    public function update(Request $request, Patient $patient, ToothChart $toothChart) { abort_unless($toothChart->patient_id === $patient->id, 404); $data=$request->validate(['tooth_number'=>['sometimes','integer','between:1,32'],'condition'=>['sometimes','required','string','max:255']]); $toothChart->update($data); return response()->json($toothChart->fresh()); }
    public function destroy(Patient $patient, ToothChart $toothChart) { abort_unless($toothChart->patient_id === $patient->id, 404); $toothChart->delete(); return response()->noContent(); }
}
