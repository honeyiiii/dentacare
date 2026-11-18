"""Train a reproducible, versioned classifier from a supplied labeled CSV."""
import argparse
from datetime import datetime, timezone
from pathlib import Path

import joblib
import pandas as pd
from sklearn.compose import ColumnTransformer
from sklearn.ensemble import RandomForestClassifier
from sklearn.impute import SimpleImputer
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OneHotEncoder


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--data', required=True, help='CSV with feature columns and a target label')
    parser.add_argument('--label', required=True, help='Target column name')
    parser.add_argument('--version', required=True, help='Immutable model version, e.g. 1.0.0')
    parser.add_argument('--output', default='models', help='Artifact output directory')
    args = parser.parse_args()

    frame = pd.read_csv(args.data)
    if args.label not in frame or frame.shape[1] < 2:
        raise SystemExit('CSV must contain the target column and at least one feature column.')
    frame = frame.dropna(subset=[args.label])
    if frame.empty:
        raise SystemExit('CSV must contain at least one row with a target label.')
    x, y = frame.drop(columns=[args.label]), frame[args.label]
    numeric = x.select_dtypes(include=['number']).columns.tolist()
    categorical = [column for column in x.columns if column not in numeric]
    transformers = []
    if numeric:
        transformers.append(('numeric', Pipeline([('impute', SimpleImputer(strategy='median'))]), numeric))
    if categorical:
        transformers.append(('categorical', Pipeline([('impute', SimpleImputer(strategy='most_frequent')), ('encode', OneHotEncoder(handle_unknown='ignore'))]), categorical))
    model = Pipeline([('features', ColumnTransformer(transformers)), ('classifier', RandomForestClassifier(n_estimators=300, class_weight='balanced', random_state=42))])
    model.fit(x, y)

    out = Path(args.output)
    out.mkdir(parents=True, exist_ok=True)
    artifact = out / f'{args.version}.joblib'
    joblib.dump({'pipeline': model, 'version': args.version, 'features': x.columns.tolist(), 'classes': [str(c) for c in model.classes_] if hasattr(model, 'classes_') else [str(c) for c in model.named_steps['classifier'].classes_], 'algorithm': 'random_forest', 'trained_at': datetime.now(timezone.utc).isoformat()}, artifact)
    print(f'Saved model artifact: {artifact}')


if __name__ == '__main__':
    main()
