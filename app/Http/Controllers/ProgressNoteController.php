<?php
namespace App\Http\Controllers;

use App\Models\ProgressNote;
use App\Models\Treatment;
use Illuminate\Http\Request;

class ProgressNoteController extends Controller
{
    public function index(Treatment $treatment)
    {
        return response()->json($treatment->progressNotes()->with('author:id,name')->latest()->paginate(20));
    }

    public function show(ProgressNote $progressNote)
    {
        return response()->json($progressNote->load('author:id,name'));
    }

    public function store(Request $request, Treatment $treatment)
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:5000']]);

        return response()->json(
            ProgressNote::create($data + ['treatment_id' => $treatment->id, 'author_id' => $request->user()->id]),
            201
        );
    }

    /** Only the author may edit their own note (the change is also kept in the audit trail). */
    public function update(Request $request, ProgressNote $progressNote)
    {
        abort_unless($progressNote->author_id === $request->user()->id, 403, 'You can only edit your own progress notes.');

        $data = $request->validate(['note' => ['required', 'string', 'max:5000']]);
        $progressNote->update($data);

        return response()->json($progressNote->fresh());
    }
}
