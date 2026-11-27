<?php

namespace App\Services;

class SymptomPredictor
{
    public const BASE = [
        'Toothache',
        'Swollen gums',
        'Sensitivity',
        'Bleeding',
        'Bad breath',
    ];

    public const MORE = [
        'Pain when chewing' => [
            'Cracked tooth',
            15,
        ],

        'Chipped tooth' => [
            'Cracked tooth',
            25,
        ],

        'Dark spots on tooth' => [
            'Cavity',
            20,
        ],

        'Sensitivity to sweets' => [
            'Cavity',
            20,
        ],

        'Loose tooth' => [
            'Gum inflammation',
            15,
        ],

        'Receding gums' => [
            'Gum inflammation',
            20,
        ],

        'Pus or abscess' => [
            'Other',
            15,
        ],

        'Swelling in face' => [
            'Other',
            10,
        ],

        'Jaw pain' => [
            'Other',
            15,
        ],

        'Mouth sores' => [
            'Other',
            20,
        ],

        'Dry mouth' => [
            'Other',
            15,
        ],

        'Fever' => [
            'Other',
            10,
        ],
    ];

    public const DURATIONS = [
        'Less than a day',
        '1 to 3 days',
        '4 to 7 days',
        'More than a week',
    ];

    private const OTHER_NAMES = [
        'Pus or abscess' => 'dental abscess (infection)',
        'Swelling in face' => 'possible infection',
        'Fever' => 'possible infection',
        'Jaw pain' => 'jaw joint (TMJ) problem',
        'Mouth sores' => 'mouth ulcers',
        'Dry mouth' => 'dry mouth (low saliva)',
    ];

    public function predict(
        array $symptoms,
        int $pain,
        string $duration,
        ?string $area = null,
        ?string $notes = null
    ): array {
        $scores = [
            'Cavity' => 10,
            'Gum inflammation' => 10,
            'Cracked tooth' => 5,
            'Other' => 8,
        ];

        $has = function (string $symptom) use ($symptoms): bool {
            return in_array($symptom, $symptoms, true);
        };

        $notesText = mb_strtolower($notes ?? '');

        /*
         * Main symptoms
         */

        if ($has('Toothache')) {
            $scores['Cavity'] += 25;
            $scores['Cracked tooth'] += 15;
        }

        if ($has('Sensitivity')) {
            $scores['Cavity'] += 25;
            $scores['Cracked tooth'] += 20;
        }

        if ($has('Swollen gums')) {
            $scores['Gum inflammation'] += 35;
        }

        if ($has('Bleeding')) {
            $scores['Gum inflammation'] += 30;
        }

        if ($has('Bad breath')) {
            $scores['Gum inflammation'] += 15;
            $scores['Cavity'] += 10;
            $scores['Other'] += 5;
        }

        /*
         * Additional symptoms
         */

        $extras = array_values(
            array_diff($symptoms, self::BASE)
        );

        foreach ($extras as $symptom) {
            if (isset(self::MORE[$symptom])) {
                $condition = self::MORE[$symptom][0];
                $weight = self::MORE[$symptom][1];

                $scores[$condition] += $weight;
            } else {
                $scores['Other'] += 10;
            }
        }

        /*
         * Pain level
         */

        if ($pain >= 7) {
            $scores['Cracked tooth'] += 10;
            $scores['Cavity'] += 5;
        }

        /*
         * Additional notes
         */

        if (preg_match('/bite|chew/', $notesText)) {
            $scores['Cracked tooth'] += 20;
        }

        /*
         * Area
         */

        if (preg_match('/gum/i', (string) $area)) {
            $scores['Gum inflammation'] += 15;
        }

        /*
         * Convert scores to percentages
         */

        $total = array_sum($scores);

        $conditions = [];

        foreach ($scores as $name => $score) {
            $percentage = (int) round(
                ($score / $total) * 100
            );

            $conditions[] = [
                $name,
                $percentage,
            ];
        }

        /*
         * Highest percentage first
         */

        usort(
            $conditions,
            function ($a, $b) {
                return $b[1] <=> $a[1];
            }
        );

        /*
         * Make percentages exactly 100
         */

        $percentageTotal = array_sum(
            array_column($conditions, 1)
        );

        $difference = 100 - $percentageTotal;

        if (isset($conditions[0])) {
            $conditions[0][1] += $difference;
        }

        /*
         * Duration index
         */

        $durationIndex = array_search(
            $duration,
            self::DURATIONS,
            true
        );

        if ($durationIndex === false) {
            $durationIndex = 0;
        }

        /*
         * Urgency score
         */

        $urgencyScore =
            $pain
            + $durationIndex
            + ($has('Bleeding') ? 1 : 0)
            + ($has('Swollen gums') ? 2 : 0)
            + ($has('Swelling in face') ? 3 : 0)
            + ($has('Pus or abscess') ? 3 : 0)
            + ($has('Fever') ? 2 : 0);

        /*
         * Urgency level
         */

        if ($urgencyScore >= 12) {
            $urgency = 'High';
        } elseif ($urgencyScore >= 8) {
            $urgency = 'Medium';
        } else {
            $urgency = 'Low';
        }

        /*
         * Recommended appointment window
         */

        $windows = [
            'High' => 'Book within 24 hours',
            'Medium' => 'Book within 3 days',
            'Low' => 'Book within 2 weeks',
        ];

        $window = $windows[$urgency];

        /*
         * Suggested next step
         */

        $steps = [
            'Cavity' =>
                'Book a dental exam and avoid very hot or cold food until then.',

            'Gum inflammation' =>
                'Brush gently, floss daily and book a cleaning and gum check.',

            'Cracked tooth' =>
                'Avoid chewing on that side and book an exam soon.',

            'Other' =>
                'Book a consultation so a dentist can look at it.',
        ];

        $topCondition = $conditions[0][0] ?? 'Other';

        $step = $steps[$topCondition] ?? $steps['Other'];

        /*
         * Emergency warning
         */

        if ($urgency === 'High') {
            $step .=
                ' If you have swelling in your face or trouble breathing, go to the emergency room.';
        }

        /*
         * Friendly label for Other
         */

        $otherNames = [];

        foreach ($extras as $symptom) {
            $otherNames[] =
                self::OTHER_NAMES[$symptom]
                ?? mb_strtolower($symptom);
        }

        $otherNames = array_values(
            array_unique($otherNames)
        );

        if (count($otherNames) > 0) {
            $otherLabel =
                'Other: ' .
                implode(
                    ', ',
                    array_slice($otherNames, 0, 2)
                );
        } elseif ($has('Bad breath')) {
            $otherLabel =
                'Other: plaque or tartar buildup';
        } else {
            $otherLabel =
                'Other: tooth wear or bite problem';
        }

        /*
         * Replace Other with friendly label
         */

        $finalConditions = array_map(
            function ($condition) use ($otherLabel) {
                return [
                    $condition[0] === 'Other'
                        ? $otherLabel
                        : $condition[0],
                    $condition[1],
                ];
            },
            $conditions
        );

        /*
         * Return result
         */

        return [
            'conditions' => $finalConditions,
            'urgency' => $urgency,
            'window' => $window,
            'step' => $step,
        ];
    }
}