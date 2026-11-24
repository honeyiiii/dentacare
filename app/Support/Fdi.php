<?php

namespace App\Support;

/** Valid tooth numbers in FDI two-digit notation (permanent + primary teeth). */
class Fdi
{
    public static function teeth(): array
    {
        $teeth = [];

        // Permanent teeth: quadrants 1-4, positions 1-8
        foreach ([1, 2, 3, 4] as $q) {
            foreach (range(1, 8) as $p) {
                $teeth[] = $q * 10 + $p;
            }
        }

        // Primary teeth: quadrants 5-8, positions 1-5
        foreach ([5, 6, 7, 8] as $q) {
            foreach (range(1, 5) as $p) {
                $teeth[] = $q * 10 + $p;
            }
        }

        return $teeth;
    }
}
