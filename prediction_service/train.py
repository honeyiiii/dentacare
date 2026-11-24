"""Train a reproducible, versioned classifier from a supplied labeled CSV."""
import argparse
import json
import math
import re
from datetime import datetime, timezone
from pathlib import Path

import joblib
import pandas as pd
from sklearn.compose import ColumnTransformer
from sklearn.ensemble import RandomForestClassifier
from sklearn.impute import SimpleImputer
from sklearn.metrics import accuracy_score, confusion_matrix, f1_score, precision_score, recall_score
from sklearn.model_selection import GroupShuffleSplit, train_test_split
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OneHotEncoder


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--data', required=True, help='CSV with feature columns and a target label')
    parser.add_argument('--label', required=True, help='Target column name')
    parser.add_argument('--version', required=True, help='Immutable model version, e.g. 1.0.0')
    parser.add_argument('--output', default='models', help='Artifact output directory')
    parser.add_argument('--test-size', type=float, default=0.2, help='Held-out evaluation fraction (default: 0.2)')
    parser.add_argument('--seed', type=int, default=42, help='Reproducible split seed (default: 42)')
    parser.add_argument('--group-column', help='Optional patient/group ID column; excluded from features and kept in one split')
    args = parser.parse_args()

    frame = pd.read_csv(args.data)
    if args.label not in frame or frame.shape[1] < 2:
        raise SystemExit('CSV must contain the target column and at least one feature column.')
    if args.group_column and (args.group_column not in frame or args.group_column == args.label):
        raise SystemExit('--group-column must name a CSV column other than the target column.')
    if not 0 < args.test_size < 1:
        raise SystemExit('--test-size must be greater than 0 and less than 1.')
    if not re.fullmatch(r'[A-Za-z0-9][A-Za-z0-9._-]{0,63}', args.version):
        raise SystemExit('--version must be a 1-64 character filename-safe version identifier.')
    if frame.columns.duplicated().any():
        raise SystemExit('CSV column names must be unique.')
    frame = frame.dropna(subset=[args.label])
    if frame.empty:
        raise SystemExit('CSV must contain at least one row with a target label.')
    excluded_columns = [args.label] + ([args.group_column] if args.group_column else [])
    x, y = frame.drop(columns=excluded_columns), frame[args.label]
    if x.shape[1] == 0:
        raise SystemExit('At least one feature column is required after excluding the target and group columns.')
    if y.nunique() < 2:
        raise SystemExit('The target column must contain at least two classes.')
    class_counts = y.value_counts()
    if (class_counts < 2).any():
        raise SystemExit('Each target class needs at least two labeled rows for stratified evaluation.')

    test_rows = math.ceil(len(frame) * args.test_size)
    if test_rows < y.nunique() or len(frame) - test_rows < y.nunique():
        raise SystemExit('The dataset/test split is too small to include every class in both sets.')

    if args.group_column:
        groups = frame[args.group_column]
        if groups.isna().any():
            raise SystemExit('The group column may not contain empty values.')
        groups_per_class = frame.groupby(args.label)[args.group_column].nunique()
        if (groups_per_class < 2).any():
            raise SystemExit('Each target class must occur in at least two groups for group-based evaluation.')

        # Try reproducible group splits and select the valid one with the closest class mix.
        candidates = GroupShuffleSplit(n_splits=100, test_size=args.test_size, random_state=args.seed)
        overall_mix = y.value_counts(normalize=True)
        best_split = None
        best_score = float('inf')
        for train_indices, test_indices in candidates.split(x, y, groups):
            y_train_candidate = y.iloc[train_indices]
            y_test_candidate = y.iloc[test_indices]
            if set(y_train_candidate) != set(y) or set(y_test_candidate) != set(y):
                continue
            test_mix = y_test_candidate.value_counts(normalize=True)
            score = sum(abs(float(test_mix.get(label, 0)) - float(share)) for label, share in overall_mix.items())
            if score < best_score:
                best_score = score
                best_split = (train_indices, test_indices)

        if best_split is None:
            raise SystemExit('Unable to make a group split that represents every target class in both sets.')

        train_indices, test_indices = best_split
        x_train, x_test = x.iloc[train_indices], x.iloc[test_indices]
        y_train, y_test = y.iloc[train_indices], y.iloc[test_indices]
    else:
        try:
            x_train, x_test, y_train, y_test = train_test_split(
                x, y, test_size=args.test_size, random_state=args.seed, stratify=y
            )
        except ValueError as error:
            raise SystemExit(f'Unable to create a stratified train/test split: {error}') from error

    numeric = x.select_dtypes(include=['number']).columns.tolist()
    categorical = [column for column in x.columns if column not in numeric]
    transformers = []
    if numeric:
        transformers.append(('numeric', Pipeline([('impute', SimpleImputer(strategy='median'))]), numeric))
    if categorical:
        transformers.append(('categorical', Pipeline([('impute', SimpleImputer(strategy='most_frequent')), ('encode', OneHotEncoder(handle_unknown='ignore'))]), categorical))
    model = Pipeline([('features', ColumnTransformer(transformers)), ('classifier', RandomForestClassifier(n_estimators=300, class_weight='balanced', random_state=42))])
    model.set_params(classifier__random_state=args.seed)
    model.fit(x_train, y_train)

    predicted = model.predict(x_test)
    labels = model.named_steps['classifier'].classes_
    metrics = {
        'accuracy': round(float(accuracy_score(y_test, predicted)), 4),
        'precision_macro': round(float(precision_score(y_test, predicted, average='macro', zero_division=0)), 4),
        'recall_macro': round(float(recall_score(y_test, predicted, average='macro', zero_division=0)), 4),
        'f1_macro': round(float(f1_score(y_test, predicted, average='macro', zero_division=0)), 4),
        'class_labels': [str(label) for label in labels],
        'confusion_matrix': confusion_matrix(y_test, predicted, labels=labels).tolist(),
    }

    out = Path(args.output)
    out.mkdir(parents=True, exist_ok=True)
    artifact = out / f'{args.version}.joblib'
    metadata_path = artifact.with_suffix('.json')
    if artifact.exists() or metadata_path.exists():
        raise SystemExit(f'Model version {args.version!r} already exists; choose a new version.')
    metadata = {
        'version': args.version,
        'algorithm': 'random_forest',
        'trained_at': datetime.now(timezone.utc).isoformat(),
        'features': x.columns.tolist(),
        'classes': [str(label) for label in labels],
        'metrics': metrics,
        'evaluation': {
            'method': 'group holdout' if args.group_column else 'stratified row holdout',
            'group_column': args.group_column,
            'test_fraction': args.test_size,
            'seed': args.seed,
            'training_rows': int(len(x_train)),
            'test_rows': int(len(x_test)),
        },
    }
    joblib.dump({'pipeline': model, **metadata}, artifact)
    metadata_path.write_text(json.dumps(metadata, indent=2))
    print(f'Saved model artifact: {artifact}')
    print(f"Held-out metrics: accuracy={metrics['accuracy']}, macro F1={metrics['f1_macro']}, test rows={len(x_test)}")


if __name__ == '__main__':
    main()
