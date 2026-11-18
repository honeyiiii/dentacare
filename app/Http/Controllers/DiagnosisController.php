<?php
namespace App\Http\Controllers;
use App\Models\Diagnosis;
use App\Models\Patient;
use Illuminate\Http\Request;
class DiagnosisController extends Controller
{
    public function index(Patient $patient) { return response()->json(Diagnosis::where('patient_id',$patient->id)->latest()->paginate(20)); }
    public function store(Request $request, Patient $patient) { $data=$request->validate(['tooth_chart_id'=>['nullable','integer','exists:tooth_charts,id'],'diagnosis'=>['required','string','max:255'],'notes'=>['nullable','string']]); if (!empty($data['tooth_chart_id'])) abort_unless(\App\Models\ToothChart::whereKey($data['tooth_chart_id'])->where('patient_id',$patient->id)->exists(), 422); return response()->json(Diagnosis::create($data+['patient_id'=>$patient->id,'dentist_id'=>$request->user()->id]),201); }
    public function update(Request $request, Patient $patient, Diagnosis $diagnosis) { abort_unless($diagnosis->patient_id===$patient->id,404); $data=$request->validate(['diagnosis'=>['sometimes','required','string','max:255'],'notes'=>['nullable','string']]); $diagnosis->update($data); return response()->json($diagnosis->fresh()); }
}
