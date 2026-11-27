<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\SymptomCheck;
use App\Services\SymptomPredictor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SymptomCheckController extends Controller
{
    public function index()
    {
        return view('symptoms.index', [
            'base' => SymptomPredictor::BASE,
            'more' => array_keys(SymptomPredictor::MORE),
            'durations' => SymptomPredictor::DURATIONS,
        ]);
    }

    public function store(Request $request, SymptomPredictor $predictor)
    {
        $data = $request->validate([
            'area' => [
                'nullable',
                'string',
                'max:100',
            ],

            'symptoms' => [
                'required',
                'array',
                'min:1',
            ],

            'symptoms.*' => [
                'string',
                'max:60',
            ],

            'pain_level' => [
                'required',
                'integer',
                'between:1,10',
            ],

            'duration' => [
                'required',
                Rule::in(SymptomPredictor::DURATIONS),
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ], [
            'symptoms.required' =>
                'Pick at least one symptom.',

            'symptoms.min' =>
                'Pick at least one symptom.',

            'duration.required' =>
                'Select how long you have had the symptoms.',
        ]);

        $symptoms = array_values(
            array_unique(
                array_filter(
                    array_map(
                        fn ($symptom) => trim(strip_tags($symptom)),
                        $data['symptoms']
                    )
                )
            )
        );

        if (empty($symptoms)) {
            return back()
                ->withInput()
                ->withErrors([
                    'symptoms' => 'Pick at least one symptom.',
                ]);
        }

        $result = $predictor->predict(
            $symptoms,
            (int) $data['pain_level'],
            $data['duration'],
            $data['area'] ?? null,
            $data['notes'] ?? null
        );

        $user = $request->user();

        $patientId = Patient::where(
            'user_id',
            $user->id
        )->value('id');

        SymptomCheck::create([
            'user_id' => $user->id,
            'patient_id' => $patientId,
            'area' => $data['area'] ?? null,
            'symptoms' => $symptoms,
            'pain_level' => (int) $data['pain_level'],
            'duration' => $data['duration'],
            'notes' => $data['notes'] ?? null,
            'conditions' => $result['conditions'],
            'urgency' => $result['urgency'],
            'recommended_window' => $result['window'],
        ]);

        return redirect()
            ->route('symptoms.index')
            ->withInput()
            ->with('result', $result);
    }
}