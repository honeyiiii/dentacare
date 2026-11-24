<?php
namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Shared logic for medical and dental history (UC-SH-08).
 * Medical alerts (UC-EX-01) exist ONLY on medical history, and only a dentist may set them.
 */
abstract class ClinicalHistoryController extends Controller
{
    protected string $modelClass;
    protected bool $supportsAlerts = false;

    /** Allowed values for the "category" field. */
    abstract protected function categories(): array;

    public function index(Patient $patient)
    {
        $model = $this->modelClass;

        $query = $model::where('patient_id', $patient->id)->with('recorder:id,name');
        if ($this->supportsAlerts) {
            $query->with('flaggedBy:id,name')->orderByDesc('is_alert');   // alerts first
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function show(Patient $patient, int $record)
    {
        $model = $this->modelClass;
        $item = $model::where('patient_id', $patient->id)->with(['recorder:id,name', 'updater:id,name'])->findOrFail($record);

        return response()->json($item);
    }

    public function store(Request $request, Patient $patient)
    {
        $data = $request->validate($this->rules());
        if ($this->supportsAlerts) {
            $data = $this->applyAlertRules($request, $data, null);
        }

        $model = $this->modelClass;
        $record = $model::create($data + ['patient_id' => $patient->id, 'recorded_by' => $request->user()->id]);

        return response()->json($record, 201);
    }

    public function update(Request $request, Patient $patient, int $record)
    {
        $model = $this->modelClass;
        $item = $model::where('patient_id', $patient->id)->findOrFail($record);

        $data = $request->validate($this->rules(partial: true));
        if ($this->supportsAlerts) {
            $data = $this->applyAlertRules($request, $data, $item);
        }
        $data['updated_by'] = $request->user()->id;

        $item->update($data);

        return response()->json($item->fresh());
    }

    private function rules(bool $partial = false): array
    {
        $req = $partial ? 'sometimes' : 'required';

        $rules = [
            'category' => [$req, Rule::in($this->categories())],
            'details' => [$req, 'string', 'max:5000'],
        ];

        if ($this->supportsAlerts) {
            $rules['is_alert'] = ['sometimes', 'boolean'];
            $rules['alert_note'] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    /**
     * Only a dentist may flag or un-flag an alert. A non-dentist can still save the entry itself;
     * the request is rejected (403) only when it actually tries to CHANGE the alert state.
     */
    private function applyAlertRules(Request $request, array $data, $existing): array
    {
        $currentFlag = (bool) ($existing?->is_alert ?? false);
        $currentNote = $existing?->alert_note;

        $flagChanged = array_key_exists('is_alert', $data) && (bool) $data['is_alert'] !== $currentFlag;
        $noteChanged = array_key_exists('alert_note', $data) && filled($data['alert_note']) && $data['alert_note'] !== $currentNote;

        if (! $flagChanged && ! $noteChanged) {
            return Arr::except($data, ['is_alert', 'alert_note']);
        }

        abort_unless($request->user()->role === 'dentist', 403, 'Only a dentist may manage clinical alerts.');

        $isAlert = (bool) ($data['is_alert'] ?? $currentFlag);

        if (! $isAlert) {
            return array_merge($data, ['is_alert' => false, 'alert_note' => null, 'flagged_by' => null, 'flagged_at' => null]);
        }

        $note = $data['alert_note'] ?? $currentNote;
        if (blank($note)) {
            throw ValidationException::withMessages(['alert_note' => 'An alert note is required when flagging an alert.']);
        }

        $data['is_alert'] = true;
        $data['alert_note'] = $note;
        if (! $currentFlag) {
            $data['flagged_by'] = $request->user()->id;
            $data['flagged_at'] = now();
        }

        return $data;
    }
}
