<?php

namespace App\Support;

use Carbon\Carbon;

/** Sample data + helpers. Data lives in the session (demo only, no database). */
class Clinic
{
    const SLOTS = ['09:00', '10:00', '11:00', '13:00', '14:00'];
    const SERVICES = ['Routine check-up', 'Routine cleaning', 'Filling', 'Check-up and X-ray', 'Tooth extraction', 'Consultation'];
    const DENTISTS = ['Dr. Maya Chen', 'Dr. Sam Lim', 'Dr. Leah'];

    public static function boot(): void
    {
        if (session()->has('appts')) return;
        $w = self::week(); $s = 'Sarah Collins';
        session([
            'appts' => [
                ['id' => 1, 'date' => $w[0], 'time' => '09:00', 'pat' => 'Juan Dela Cruz', 'svc' => 'Routine check-up', 'dr' => 'Dr. Sam Lim', 'st' => 'Booked'],
                ['id' => 2, 'date' => $w[1], 'time' => '10:00', 'pat' => 'Mark Dizon', 'svc' => 'Filling', 'dr' => 'Dr. Leah', 'st' => 'Booked'],
                ['id' => 3, 'date' => $w[2], 'time' => '11:00', 'pat' => $s, 'svc' => 'Routine check-up', 'dr' => 'Dr. Maya Chen', 'st' => 'Booked'],
                ['id' => 4, 'date' => '2026-03-12', 'time' => '09:00', 'pat' => $s, 'svc' => 'Routine cleaning', 'dr' => 'Dr. Maya Chen', 'st' => 'Completed'],
                ['id' => 5, 'date' => '2025-09-03', 'time' => '10:00', 'pat' => $s, 'svc' => 'Check-up and X-ray', 'dr' => 'Dr. Maya Chen', 'st' => 'Completed'],
                ['id' => 6, 'date' => '2025-03-08', 'time' => '11:00', 'pat' => $s, 'svc' => 'Filling', 'dr' => 'Dr. Sam Lim', 'st' => 'Completed'],
            ],
            'queue' => [['n' => 'Juan Dela Cruz', 's' => 'Routine check-up', 'd' => 'Dr. Sam Lim', 'st' => 'In chair'], ['n' => 'Mark Dizon', 's' => 'Filling', 'd' => 'Dr. Leah', 'st' => 'Waiting']],
            'rem' => [['n' => 'Ana Reyes', 'm' => 'Appointment tomorrow 9:00', 't' => 'SMS'], ['n' => 'Leo Tan', 'm' => 'Six-month cleaning due', 't' => 'Email'], ['n' => 'Mia Cruz', 'm' => 'Follow-up after filling', 't' => 'SMS']],
            'user' => ['name' => $s, 'email' => 'sarah@email.com', 'dob' => '', 'sex' => 'Female', 'phone' => '', 'addr' => '', 'photo' => ''],
            'admin' => ['name' => 'Front Desk Staff', 'role' => 'Front desk', 'email' => 'frontdesk@dentacare.ph', 'phone' => '', 'photo' => ''],
        ]);
    }

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

    public static function idx($id): ?int
    {
        foreach (session('appts') as $i => $a) if ($a['id'] === (int) $id) return $i;
        return null;
    }

    public static function taken($d, $t, $dr, $skip = 0): bool
    {
        foreach (session('appts') as $a)
            if ($a['st'] === 'Booked' && $a['date'] === $d && $a['time'] === $t && $a['dr'] === $dr && $a['id'] !== $skip) return true;
        return false;
    }

    public static function add($d, $t, $p, $s, $dr, string $notes = ''): void
    {
        $appts = session('appts'); $ids = array_column($appts, 'id');
        $appts[] = ['id' => ($ids ? max($ids) : 0) + 1, 'date' => $d, 'time' => $t, 'pat' => $p, 'svc' => $s, 'dr' => $dr, 'st' => 'Booked', 'notes' => $notes];
        $rem = session('rem'); $rem[] = ['n' => $p, 'm' => 'Appointment ' . self::fdate($d) . ' ' . self::ftime($t), 't' => 'SMS'];
        session(['appts' => $appts, 'rem' => $rem]);
    }

    public static function mine($st = null): array
    {
        $o = array_values(array_filter(session('appts'), fn($a) => $a['pat'] === session('user.name') && ($st === null || $a['st'] === $st)));
        usort($o, fn($a, $b) => strcmp($b['date'] . $b['time'], $a['date'] . $a['time']));
        return $o;
    }

    /** Saves an uploaded avatar into public/uploads and returns its relative path (or null). */
    public static function photo($file, string $key): ?string
    {
        if (!$file) return null;
        $name = $key . '_' . substr(md5(session()->getId()), 0, 10) . '.' . $file->extension();
        $file->move(public_path('uploads'), $name);
        return 'uploads/' . $name;
    }
}
