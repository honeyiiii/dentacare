# Prediction support service

This service only loads a model trained from a real, appropriately governed labeled dataset. It does not ship a model or clinical labels.

```powershell
cd prediction_service
python -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install -r requirements.txt
python train.py --data path\to\approved_training_data.csv --label outcome --version 1.0.0
$env:MODEL_PATH = 'models/1.0.0.joblib'
$env:PREDICTION_SERVICE_API_KEY = 'use-the-same-long-random-value-as-Laravel'
uvicorn app:app --host 127.0.0.1 --port 8001
```

Set the same secret as `PREDICTION_SERVICE_API_KEY` in Laravel's `.env`. Requests to `POST /predict` without that key are rejected. Keep the service bound to localhost unless you also configure transport security and network access controls.

Each CSV column except `--label` becomes a model input, and all such features are required by `POST /predict` as `{"input_data": {"feature": "value"}}`. The response includes class probability scores and model version. Keep training data out of source control. Laravel calls `PREDICTION_SERVICE_URL` (default `http://127.0.0.1:8001`). Every saved result carries the required `supporting information only` label.
