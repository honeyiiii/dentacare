import os
import hmac
from functools import lru_cache

import joblib
import pandas as pd
from fastapi import FastAPI, Header, HTTPException
from fastapi.responses import JSONResponse
from pydantic import BaseModel, Field

app = FastAPI(title='DentaCare prediction support', version='1.0.0')


class PredictionInput(BaseModel):
    input_data: dict[str, str | int | float | bool | None] = Field(min_length=1)


@lru_cache(maxsize=1)
def load_model():
    path = os.getenv('MODEL_PATH', 'models/current.joblib')
    if not os.path.isfile(path):
        raise HTTPException(status_code=503, detail='No trained model artifact is configured.')
    return joblib.load(path)


@app.get('/health')
def health():
    try:
        model = load_model()
        return {'status': 'ready', 'model_version': model['version']}
    except HTTPException:
        return JSONResponse(status_code=503, content={'status': 'model_not_configured'})


def authorize(x_api_key: str | None) -> None:
    expected_key = os.getenv('PREDICTION_SERVICE_API_KEY')
    if not expected_key or not x_api_key or not hmac.compare_digest(x_api_key, expected_key):
        raise HTTPException(status_code=401, detail='Unauthorized.')


@app.get('/model-info')
def model_info(x_api_key: str | None = Header(default=None)):
    authorize(x_api_key)
    model = load_model()
    return {key: model.get(key) for key in ('version', 'algorithm', 'trained_at', 'features', 'classes', 'metrics', 'evaluation')}


@app.post('/predict')
def predict(payload: PredictionInput, x_api_key: str | None = Header(default=None)):
    authorize(x_api_key)
    model = load_model()
    expected = model['features']
    missing = [feature for feature in expected if feature not in payload.input_data]
    extra = [feature for feature in payload.input_data if feature not in expected]
    if missing or extra:
        raise HTTPException(status_code=422, detail={'missing_features': missing, 'unknown_features': extra})
    row = pd.DataFrame([{key: payload.input_data[key] for key in expected}])
    pipeline = model['pipeline']
    result = str(pipeline.predict(row)[0])
    scores = {}
    if hasattr(pipeline, 'predict_proba'):
        scores = {str(label): round(float(score), 6) for label, score in zip(pipeline.named_steps['classifier'].classes_, pipeline.predict_proba(row)[0])}
    return {
        'result': result,
        'confidence': max(scores.values()) if scores else None,
        'risk_scores': scores,
        'model_version': model['version'],
        'algorithm': model['algorithm'],
        'trained_at': model['trained_at'],
        'metrics': model['metrics'],
        'label': 'supporting information only',
    }
