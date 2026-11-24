"""Train the predictive model and save it with joblib.

    python -m dentacare_ml.train --data data/synthetic_dental.csv --algo forest
    python -m dentacare_ml.train --data data/real.csv --algo tree

CSV columns: every name in features.FEATURES plus a "condition" column using values from features.CONDITIONS.
Output: models/<version>.joblib (+ models/<version>.json metadata) and models/latest.json pointing at it.
"""
from __future__ import annotations

import argparse
import json
from datetime import datetime, timezone
from pathlib import Path

import joblib
import pandas as pd
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix, f1_score
from sklearn.model_selection import train_test_split
from sklearn.tree import DecisionTreeClassifier

from .features import CONDITIONS, FEATURE_NAMES, TARGET


def load_dataset(path: str | Path) -> pd.DataFrame:
    df = pd.read_csv(path)
    missing = [c for c in FEATURE_NAMES + [TARGET] if c not in df.columns]
    if missing:
        raise ValueError(f"Dataset is missing columns: {missing}")
    bad = sorted(set(df[TARGET].unique()) - set(CONDITIONS))
    if bad:
        raise ValueError(f"Unknown condition labels in dataset: {bad}. Allowed: {CONDITIONS}")
    df = df[FEATURE_NAMES + [TARGET]].dropna()
    if len(df) < 100:
        raise ValueError("Dataset is too small to train (need at least 100 complete rows).")
    return df


def build_estimator(algo: str, seed: int):
    if algo == "forest":
        return "random_forest", RandomForestClassifier(
            n_estimators=200, max_depth=12, min_samples_leaf=3,
            class_weight="balanced", n_jobs=-1, random_state=seed,
        )
    if algo == "tree":
        return "decision_tree", DecisionTreeClassifier(
            max_depth=8, min_samples_leaf=5, class_weight="balanced", random_state=seed,
        )
    raise ValueError("algo must be 'forest' or 'tree'")


def train(data_path: str, algo: str = "forest", models_dir: str = "models", seed: int = 42,
          test_size: float = 0.2, notes: str = "") -> dict:
    df = load_dataset(data_path)
    X, y = df[FEATURE_NAMES], df[TARGET]

    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=test_size, random_state=seed, stratify=y
    )

    algorithm, model = build_estimator(algo, seed)
    model.fit(X_train, y_train)

    pred = model.predict(X_test)
    report = classification_report(y_test, pred, output_dict=True, zero_division=0)
    metrics = {
        "accuracy": round(float(accuracy_score(y_test, pred)), 4),
        "macro_f1": round(float(f1_score(y_test, pred, average="macro")), 4),
        "per_class_recall": {c: round(float(report[c]["recall"]), 4) for c in model.classes_},
        "test_rows": int(len(y_test)),
        "train_rows": int(len(y_train)),
        "confusion_matrix": {"labels": list(model.classes_),
                             "matrix": confusion_matrix(y_test, pred, labels=model.classes_).tolist()},
    }

    now = datetime.now(timezone.utc)
    prefix = "rf" if algo == "forest" else "dt"
    version = f"{prefix}-{now.strftime('%Y%m%d-%H%M%S')}"

    out_dir = Path(models_dir)
    out_dir.mkdir(parents=True, exist_ok=True)
    artifact = out_dir / f"{version}.joblib"

    meta = {
        "version": version,
        "algorithm": algorithm,
        "trained_at": now.isoformat(),
        "artifact_path": str(artifact),
        "features": FEATURE_NAMES,
        "classes": [str(c) for c in model.classes_],
        "metrics": metrics,
        "data_source": str(data_path),
        "notes": notes,
    }

    joblib.dump({"model": model, "meta": meta}, artifact)
    (out_dir / f"{version}.json").write_text(json.dumps(meta, indent=2))
    (out_dir / "latest.json").write_text(json.dumps({"version": version, "artifact": artifact.name}, indent=2))
    return meta


def main() -> None:
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("--data", required=True)
    ap.add_argument("--algo", choices=["forest", "tree"], default="forest")
    ap.add_argument("--models-dir", default="models")
    ap.add_argument("--seed", type=int, default=42)
    ap.add_argument("--notes", default="")
    args = ap.parse_args()

    meta = train(args.data, args.algo, args.models_dir, args.seed, notes=args.notes)
    m = meta["metrics"]
    print(f"Trained {meta['algorithm']} -> {meta['version']}")
    print(f"  accuracy={m['accuracy']}  macro_f1={m['macro_f1']}  (train={m['train_rows']}, test={m['test_rows']})")
    print(f"  per-class recall: {m['per_class_recall']}")
    print(f"  saved: {meta['artifact_path']}")


if __name__ == "__main__":
    main()
