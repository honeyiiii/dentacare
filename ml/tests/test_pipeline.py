import pytest

from dentacare_ml.features import CONDITIONS, FEATURE_NAMES, NOTICE
from dentacare_ml.service import create_app
from dentacare_ml.synthetic_data import generate
from dentacare_ml.train import train

KEY = "test-key"

HEALTHY = dict(age=25, pain_level=0, spontaneous_pain=0, sensitivity_hot_cold=0, sensitivity_sweets=0,
               visible_cavity=0, bleeding_gums=0, swelling=0, tooth_mobility=0, plaque_level=0,
               brushing_per_day=3, sugar_intake_per_day=1, smoker=0, diabetic=0, months_since_last_visit=4)

PULPITIS_LIKE = dict(HEALTHY, pain_level=8, spontaneous_pain=1, sensitivity_hot_cold=1,
                     visible_cavity=1, swelling=1, sugar_intake_per_day=6, plaque_level=2)


@pytest.fixture(scope="module")
def client(tmp_path_factory):
    d = tmp_path_factory.mktemp("work")
    csv = d / "data.csv"
    generate(2500).to_csv(csv, index=False)
    train(str(csv), "forest", str(d / "models"))
    app = create_app(models_dir=str(d / "models"), api_key=KEY)
    app.config["TESTING"] = True
    return app.test_client()


def post(client, features, key=KEY):
    return client.post("/predict", json={"features": features}, headers={"X-API-Key": key} if key else {})


def test_dataset_has_all_columns_and_classes():
    df = generate(1500)
    assert set(FEATURE_NAMES + ["condition"]) == set(df.columns)
    assert set(df["condition"]) == set(CONDITIONS)


def test_health_has_no_patient_data(client):
    r = client.get("/health")
    assert r.status_code == 200 and r.json["status"] == "ok"


def test_predict_shape_and_notice(client):
    r = post(client, HEALTHY)
    assert r.status_code == 200
    j = r.json
    assert j["result"] in CONDITIONS
    assert set(j["risk_scores"]) == set(CONDITIONS)
    assert abs(sum(j["risk_scores"].values()) - 1) < 0.01
    assert j["confidence"] == j["risk_scores"][j["result"]]
    assert j["notice"] == NOTICE
    assert {"version", "algorithm", "metrics"} <= set(j["model"])


def test_symptom_pattern_changes_result(client):
    assert post(client, PULPITIS_LIKE).json["risk_scores"]["pulpitis"] > post(client, HEALTHY).json["risk_scores"]["pulpitis"]


def test_laravel_contract(client):
    """Laravel sends {"input_data": {...}} and reads flat model_version / algorithm / metrics."""
    r = client.post("/predict", json={"input_data": HEALTHY}, headers={"X-API-Key": KEY})
    assert r.status_code == 200
    j = r.json
    assert j["model_version"] and j["algorithm"] in ("random_forest", "decision_tree")
    assert "accuracy" in j["metrics"] and j["trained_at"]
    assert j["risk_scores"] and j["result"] in j["risk_scores"]


def test_requires_api_key(client):
    assert post(client, HEALTHY, key=None).status_code == 401
    assert post(client, HEALTHY, key="wrong").status_code == 401


def test_validation_errors(client):
    bad = dict(HEALTHY)
    del bad["age"]
    bad["pain_level"] = 99
    r = post(client, bad)
    assert r.status_code == 422
    assert any("age" in e for e in r.json["detail"]) and any("pain_level" in e for e in r.json["detail"])


def test_service_refuses_to_start_without_key(monkeypatch, tmp_path):
    monkeypatch.delenv("DENTACARE_ML_API_KEY", raising=False)
    with pytest.raises(RuntimeError):
        create_app(models_dir=str(tmp_path))


def test_no_model_returns_503(tmp_path):
    app = create_app(models_dir=str(tmp_path), api_key=KEY)
    r = app.test_client().post("/predict", json={"features": HEALTHY}, headers={"X-API-Key": KEY})
    assert r.status_code == 503


def test_reload(client):
    assert client.post("/reload", headers={"X-API-Key": KEY}).json["status"] == "reloaded"
