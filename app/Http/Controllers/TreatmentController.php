<?php
namespace App\Http\Controllers;
use App\Models\Patient;
use App\Models\Treatment;
use Illuminate\Http\Request;
class TreatmentController extends Controller
{
    public function index(Patient $patient) { return response()->json(Treatment::with('progressNotes')->where('patient_id',$patient->id)->latest()->paginate(20)); }
    public function store(Request $request, Patient $patient) { $data=$request->validate(['tooth_chart_id'=>['nullable','integer','exists:tooth_charts,id'],'procedure'=>['required','string','max:255'],'status'=>['sometimes','in:planned,in_progress,completed,cancelled'],'treatment_date'=>['nullable','date']]); if (!empty($data['tooth_chart_id'])) abort_unless(\App\Models\ToothChart::whereKey($data['tooth_chart_id'])->where('patient_id',$patient->id)->exists(),422); return response()->json(Treatment::create($data+['patient_id'=>$patient->id,'recorded_by'=>$request->user()->id]),201); }
    public function update(Request $request, Patient $patient, Treatment $treatment) { abort_unless($treatment->patient_id===$patient->id,404); $data=$request->validate(['procedure'=>['sometimes','required','string','max:255'],'status'=>['sometimes','in:planned,in_progress,completed,cancelled'],'treatment_date'=>['nullable','date']]); $treatment->update($data); return response()->json($treatment->fresh()); }
}
