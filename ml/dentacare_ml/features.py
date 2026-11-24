"""Single source of truth for the model's inputs and outputs.

Keep this in sync with:
  * laravel/app/Services/PredictionService.php  (FEATURE_RULES)
  * laravel/config/prediction.php               (conditions)
"""
from __future__ import annotations

NOTICE = (
    "Supporting information only. This is not a diagnosis; "
    "the final decision rests with the licensed dentist."
)

CONDITIONS = ["healthy", "dental_caries", "gingivitis", "periodontitis", "pulpitis"]

# name -> (min, max). Binary yes/no features are (0, 1).
FEATURES: dict[str, tuple[int, int]] = {
    "age": (1, 120),
    "pain_level": (0, 10),
    "spontaneous_pain": (0, 1),
    "sensitivity_hot_cold": (0, 1),
    "sensitivity_sweets": (0, 1),
    "visible_cavity": (0, 1),
    "bleeding_gums": (0, 1),
    "swelling": (0, 1),
    "tooth_mobility": (0, 3),          # Miller grade
    "plaque_level": (0, 3),
    "brushing_per_day": (0, 5),
    "sugar_intake_per_day": (0, 10),   # sugary servings per day
    "smoker": (0, 1),
    "diabetic": (0, 1),
    "months_since_last_visit": (0, 240),
}

FEATURE_NAMES = list(FEATURES)
TARGET = "condition"


def validate_features(payload) -> tuple[dict[str, int], list[str]]:
    """Return (clean_values, errors). Every feature is required and must be an in-range integer."""
    errors: list[str] = []
    clean: dict[str, int] = {}

    if not isinstance(payload, dict):
        return {}, ["'features' must be an object"]

    for name, (lo, hi) in FEATURES.items():
        if name not in payload:
            errors.append(f"{name}: required")
            continue
        value = payload[name]
        if isinstance(value, bool):
            value = int(value)
        if isinstance(value, float) and value.is_integer():
            value = int(value)
        if not isinstance(value, int):
            errors.append(f"{name}: must be an integer")
            continue
        if not lo <= value <= hi:
            errors.append(f"{name}: must be between {lo} and {hi}")
            continue
        clean[name] = value

    unknown = sorted(set(payload) - set(FEATURES))
    if unknown:
        errors.append("unknown features: " + ", ".join(unknown))

    return clean, errors
