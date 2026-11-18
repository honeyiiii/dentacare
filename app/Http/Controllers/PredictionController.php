<?php
namespace App\Http\Controllers;

use App\Models\ModelVersion;
use App\Models\Patient;
use App\Models\Prediction;
use App\Models\PredictionDecision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class PredictionController extends Controller
{
    public function index(Patient $patient) { return response()->json(Prediction::with('decisions')->where('patient_id',$patient->id)->latest()->paginate(20)); }

    public function store(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'input_data' => ['required', 'array', 'min:1', 'max:100'],
            'input_data.*' => [function ($attribute, $value, $fail) {
                if ($value !== null && ! is_scalar($value)) {
                    $fail('Each prediction feature must be a scalar value.');
                }
                if (is_string($value) && strlen($value) > 255) {
                    $fail('Prediction feature values may not exceed 255 characters.');
                }
            }],
        ]);
        $apiKey = (string) config('services.prediction.api_key');
        if ($apiKey === '') {
            report(new \RuntimeException('Prediction service API key is not configured.'));
            return response()->json(['message' => 'Prediction service is unavailable.'], 503);
        }
        try {
            $response = Http::timeout((int) config('services.prediction.timeout', 10))
                ->withHeader('X-API-Key', $apiKey)
                ->post(rtrim(config('services.prediction.url', 'http://127.0.0.1:8001'), '/').'/predict', ['input_data'=>$data['input_data']]);
        } catch (ConnectionException) {
            return response()->json(['message'=>'Prediction service is unavailable.'], 503);
        }
        if ($response->status() === 422) return response()->json(['message'=>'Prediction inputs do not match the trained model features.', 'errors'=>$response->json('detail')], 422);
        if (!$response->successful()) return response()->json(['message'=>'Prediction service is unavailable or could not process these inputs.'], 503);
        $result = $response->json();
        $version = isset($result['model_version']) ? ModelVersion::firstOrCreate(
            ['version' => $result['model_version']],
            ['algorithm' => $result['algorithm'] ?? 'random_forest', 'artifact_path' => 'configured prediction service', 'trained_at' => now()]
        ) : null;
        $prediction = Prediction::create(['patient_id'=>$patient->id,'input_data'=>$data['input_data'],'result'=>$result['result'],'risk_scores'=>$result['risk_scores'] ?? null,'model_version_id'=>$version?->id,'model_version'=>$result['model_version'] ?? null,'label'=>'supporting information only','requested_by'=>$request->user()->id]);
        return response()->json($prediction,201);
    }

    public function decide(Request $request, Prediction $prediction)
    {
        $data = $request->validate(['decision'=>['required','in:confirmed,overridden'],'note'=>['nullable','string','max:2000']]);
        $decision = PredictionDecision::updateOrCreate(['prediction_id'=>$prediction->id,'dentist_id'=>$request->user()->id],$data);
        return response()->json($decision, 201);
    }
}
