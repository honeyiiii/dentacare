<?php
namespace App\Http\Controllers;
use App\Models\ProgressNote;
use App\Models\Treatment;
use Illuminate\Http\Request;
class ProgressNoteController extends Controller
{
    public function index(Treatment $treatment) { return response()->json($treatment->progressNotes()->latest()->paginate(20)); }
    public function show(ProgressNote $progressNote) { return response()->json($progressNote); }
    public function store(Request $request, Treatment $treatment) { $data=$request->validate(['note'=>['required','string']]); return response()->json(ProgressNote::create($data+['treatment_id'=>$treatment->id,'author_id'=>$request->user()->id]),201); }
    public function update(Request $request, ProgressNote $progressNote) { $data=$request->validate(['note'=>['required','string']]); $progressNote->update($data); return response()->json($progressNote->fresh()); }
}
