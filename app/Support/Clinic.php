<?php

namespace App\Support;

use Carbon\Carbon;

/** Clinic constants and display helpers. Appointment and patient data lives in the database. */
class Clinic
{
    const SLOTS = ['09:00', '10:00', '11:00', '13:00', '14:00'];
    const SERVICES = ['Routine check-up', 'Routine cleaning', 'Filling', 'Check-up and X-ray', 'Tooth extraction', 'Consultation'];
    const DENTISTS = ['Dr. Maya Chen', 'Dr. Sam Lim', 'Dr. Leah'];

    public static function boot(): void {}

    public static function week(): array
    {
        $m = Carbon::now()->startOfWeek();
        return array_map(fn($i) => $m->copy()->addDays($i)->toDateString(), range(0, 4));
    }

    public static function fdate($d) { return Carbon::parse($d)->format('M j, Y'); }
    public static function ftime($t) { return Carbon::parse($t)->format('g:i'); }

    public static function initials($n): string
    {
        $o = ''; foreach (preg_split('/\s+/', trim($n)) as $w) $o .= strtoupper(substr($w, 0, 1));
        return substr($o, 0, 2);
    }

    public static function pill($s): string
    {
        $c = ['Completed' => 'ok', 'Done' => 'ok', 'Cancelled' => 'bad'][$s] ?? '';
        return '<span class="pill ' . $c . '">' . e($s) . '</span>';
    }

}
