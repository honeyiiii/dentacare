"""Small Flask service that Laravel calls to get a prediction.

    PowerShell:  $env:PREDICTION_SERVICE_API_KEY="same-key-as-in-laravel-.env"
    python -m dentacare_ml.service         # dev, http://127.0.0.1:8001  (matches PREDICTION_SERVICE_URL)
    waitress-serve --listen=127.0.0.1:8001 dentacare_ml.service:app   # production-style

Endpoints (all except /health require the X-API-Key header):
    GET  /health    -> status + active model info (no key needed, contains no patient data)
    POST /predict   -> {"input_data": {...}}  (alias: "features")
                       => result, confidence, risk_scores, model_version, algorithm, trained_at, metrics, notice
                       invalid input => HTTP 422 with {"detail": [...]}   (what the Laravel PredictionController expects)
    POST /reload    -> reload models/latest.json after retraining (used by Task 4)
"""
from __future__ import annotations

import hmac
import json
import os
from pathlib import Path

import joblib
import pandas as pd
from flask import Flask, jsonify, request

from .features import FEATURE_NAMES, NOTICE, validate_features


class ModelStore:
    def __init__(self, models_dir: str):
        self.models_dir = Path(models_dir)
        self.model = None
        self.meta: dict | None = None

    def load(self) -> None:
        pointer = self.models_dir / "latest.json"
        if not pointer.exists():
            raise FileNotFoundError(f"No trained model found: {pointer} is missing. Run dentacare_ml.train first.")
        artifact = self.models_dir / json.loads(pointer.read_text())["artifact"]
        bundle = joblib.load(artifact)
        self.model, self.meta = bundle["model"], bundle["meta"]


def create_app(models_dir: str | None = None, api_key: str | None = None) -> Flask:
    app = Flask(__name__)
    app.config["API_KEY"] = (
        api_key or os.environ.get("PREDICTION_SERVICE_API_KEY") or os.environ.get("DENTACARE_ML_API_KEY")
    )
    if not app.config["API_KEY"]:
        raise RuntimeError("Set PREDICTION_SERVICE_API_KEY (must match PREDICTION_SERVICE_API_KEY in Laravel .env).")

    store = ModelStore(models_dir or os.environ.get("DENTACARE_ML_MODELS_DIR", "models"))
    try:
        store.load()
    except FileNotFoundError as exc:
        app.logger.warning("%s", exc)   # service still starts; /predict answers 503 until a model exists

    def authorised() -> bool:
        supplied = request.headers.get("X-API-Key", "")
        return hmac.compare_digest(supplied.encode(), app.config["API_KEY"].encode())

    def model_info() -> dict:
        m = store.meta
        return {k: m[k] for k in ("version", "algorithm", "trained_at", "artifact_path", "metrics")}

    @app.get("/health")
    def health():
        if store.model is None:
            return jsonify(status="no_model"), 503
        return jsonify(status="ok", model={k: v for k, v in model_info().items() if k != "metrics"})

    @app.post("/predict")
    def predict():
        if not authorised():
            return jsonify(error="unauthorised"), 401
        if store.model is None:
            return jsonify(error="no trained model loaded"), 503

        body = request.get_json(silent=True)
        payload = None
        if isinstance(body, dict):
            payload = body.get("input_data", body.get("features"))
        if payload is None:
            return jsonify(error="expected JSON body with an 'input_data' object", detail=["input_data: required"]), 422

        clean, errors = validate_features(payload)
        if errors:
            return jsonify(error="invalid input", detail=errors), 422

        row = pd.DataFrame([clean], columns=FEATURE_NAMES)
        probs = store.model.predict_proba(row)[0]
        scores = {str(c): round(float(p), 4) for c, p in zip(store.model.classes_, probs)}
        top = max(scores, key=scores.get)

        info = model_info()
        return jsonify(
            result=top,
            confidence=scores[top],
            risk_scores=dict(sorted(scores.items(), key=lambda kv: kv[1], reverse=True)),
            # flat fields read by Laravel's PredictionController
            model_version=info["version"],
            algorithm=info["algorithm"],
            trained_at=info["trained_at"],
            metrics=info["metrics"],
            model=info,
            notice=NOTICE,
        )

    @app.post("/reload")
    def reload_model():
        if not authorised():
            return jsonify(error="unauthorised"), 401
        try:
            store.load()
        except FileNotFoundError as exc:
            return jsonify(error=str(exc)), 503
        return jsonify(status="reloaded", model=model_info())

    return app


# Module-level `app` for WSGI servers (waitress/gunicorn). Created lazily so importing this
# module in tests or tooling does not require the environment variable.
def __getattr__(name):
    if name == "app":
        return create_app()
    raise AttributeError(name)


if __name__ == "__main__":
    create_app().run(host="127.0.0.1", port=int(os.environ.get("PORT", 8001)))
