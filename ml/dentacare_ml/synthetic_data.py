"""Generate a SYNTHETIC development dataset.

IMPORTANT: this data is made up from simple clinical rules of thumb plus noise. It exists only so the
pipeline can be built and tested end to end. A model trained on it says NOTHING about real patients.
Replace it with a properly sourced / consented / anonymised dataset before any evaluation
(see README: "Using real data").

    python -m dentacare_ml.synthetic_data --rows 6000 --out data/synthetic_dental.csv
"""
from __future__ import annotations

import argparse
from pathlib import Path

import numpy as np
import pandas as pd

from .features import CONDITIONS, FEATURE_NAMES, TARGET


def generate(rows: int = 6000, seed: int = 42) -> pd.DataFrame:
    rng = np.random.default_rng(seed)

    age = np.clip(rng.normal(34, 16, rows).round(), 3, 90).astype(int)
    smoker = (rng.random(rows) < np.where(age > 18, 0.22, 0.01)).astype(int)
    diabetic = (rng.random(rows) < np.clip((age - 25) / 250, 0.005, 0.3)).astype(int)
    brushing = np.clip(rng.choice([0, 1, 2, 3], rows, p=[0.05, 0.25, 0.60, 0.10]), 0, 5)
    sugar = np.clip(rng.poisson(3, rows), 0, 10)
    months = np.clip(rng.gamma(2.0, 7.0, rows).round(), 0, 240).astype(int)
    plaque = np.clip(np.round(2.2 - 0.6 * brushing + 0.12 * sugar + rng.normal(0, 0.6, rows)), 0, 3).astype(int)

    # Latent "strength" of each condition for each patient (rules of thumb + noise)
    caries = 0.35 * sugar + 0.55 * plaque - 0.4 * brushing + 0.02 * months + rng.normal(0, 1.0, rows)
    gingi = 0.9 * plaque + 0.3 * smoker + 0.15 * diabetic - 0.25 * brushing + rng.normal(0, 1.0, rows)
    perio = 0.035 * age + 0.8 * smoker + 0.9 * diabetic + 0.6 * plaque + 0.02 * months + rng.normal(0, 1.1, rows)
    pulp = 0.35 * caries + rng.normal(0, 1.2, rows)

    latent = np.vstack([np.zeros(rows) + 2.6, caries, gingi, perio - 1.6, pulp])  # row 0 = healthy baseline
    latent = latent - latent.max(axis=0)
    probs = np.exp(latent) / np.exp(latent).sum(axis=0)
    cond_idx = np.array([rng.choice(len(CONDITIONS), p=probs[:, i]) for i in range(rows)])
    cond = np.array(CONDITIONS)[cond_idx]

    def flag(p):
        return (rng.random(rows) < p).astype(int)

    is_c, is_g, is_p, is_u = (cond == "dental_caries"), (cond == "gingivitis"), (cond == "periodontitis"), (cond == "pulpitis")

    df = pd.DataFrame({
        "age": age,
        "pain_level": np.clip(np.round(np.select([is_u, is_c, is_p, is_g], [7, 3, 2, 1], 0.3) + rng.normal(0, 1.6, rows)), 0, 10).astype(int),
        "spontaneous_pain": flag(np.select([is_u, is_c], [0.75, 0.05], 0.02)),
        "sensitivity_hot_cold": flag(np.select([is_u, is_c, is_p], [0.8, 0.45, 0.25], 0.07)),
        "sensitivity_sweets": flag(np.select([is_c, is_u], [0.7, 0.4], 0.08)),
        "visible_cavity": flag(np.select([is_c, is_u], [0.8, 0.55], 0.03)),
        "bleeding_gums": flag(np.select([is_g, is_p], [0.88, 0.75], 0.06)),
        "swelling": flag(np.select([is_u, is_p, is_g], [0.35, 0.25, 0.06], 0.02)),
        "tooth_mobility": np.where(is_p, rng.choice([0, 1, 2, 3], rows, p=[0.2, 0.4, 0.28, 0.12]),
                                   rng.choice([0, 1], rows, p=[0.97, 0.03])),
        "plaque_level": plaque,
        "brushing_per_day": brushing,
        "sugar_intake_per_day": sugar,
        "smoker": smoker,
        "diabetic": diabetic,
        "months_since_last_visit": months,
        TARGET: cond,
    })
    return df[FEATURE_NAMES + [TARGET]]


def main() -> None:
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("--rows", type=int, default=6000)
    ap.add_argument("--seed", type=int, default=42)
    ap.add_argument("--out", default="data/synthetic_dental.csv")
    args = ap.parse_args()

    out = Path(args.out)
    out.parent.mkdir(parents=True, exist_ok=True)
    df = generate(args.rows, args.seed)
    df.to_csv(out, index=False)
    print(f"Wrote {len(df)} SYNTHETIC rows to {out}")
    print(df[TARGET].value_counts().to_string())


if __name__ == "__main__":
    main()
