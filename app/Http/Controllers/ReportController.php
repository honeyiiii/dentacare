<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    private const TYPES = [
        'appointments'  => 'Appointment schedule',
        'per-dentist'   => 'Appointments per dentist',
        'patients'      => 'Patient registrations',
        'cancellations' => 'Cancelled appointments',
    ];

    public function index(Request $request)
    {
        $type = $request->query('type', 'appointments');
        abort_unless(array_key_exists($type, self::TYPES), 404);

        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to   = $request->query('to', now()->endOfMonth()->toDateString());

        [$headings, $rows] = match ($type) {
            'appointments' => [
                ['Date', 'Time', 'Patient', 'Dentist', 'Service', 'Status'],
                $this->appointmentRows(Appointment::with(['patient', 'dentist']), $from, $to),
            ],
            'cancellations' => [
                ['Date', 'Time', 'Patient', 'Dentist', 'Service', 'Status'],
                $this->appointmentRows(Appointment::with(['patient', 'dentist'])->where('status', 'cancelled'), $from, $to),
            ],
            'per-dentist' => [
                ['Dentist', 'Total', 'Scheduled', 'Cancelled'],
                DB::table('appointments')
                    ->join('users', 'users.id', '=', 'appointments.dentist_id')
                    ->whereBetween('appointments.appointment_date', [$from, $to])
                    ->groupBy('users.name')
                    ->orderBy('users.name')
                    ->selectRaw("users.name as dentist, COUNT(*) as total, SUM(appointments.status = 'scheduled') as scheduled, SUM(appointments.status = 'cancelled') as cancelled")
                    ->get()
                    ->map(fn ($r) => [$r->dentist, $r->total, (int) $r->scheduled, (int) $r->cancelled])
                    ->all(),
            ],
            'patients' => [
                ['Name', 'Birth date', 'Sex', 'Contact', 'Registered'],
                Patient::whereDate('created_at', '>=', $from)
                    ->whereDate('created_at', '<=', $to)
                    ->orderBy('full_name')
                    ->get()
                    ->map(fn ($p) => [
                        $p->full_name,
                        $p->date_of_birth,
                        $p->sex,
                        $p->contact_number,
                        $p->created_at->format('M d, Y'),
                    ])->all(),
            ],
        };

        $types = self::TYPES;

        return view('reports.index', compact('type', 'types', 'from', 'to', 'headings', 'rows'));
    }

    private function appointmentRows($query, string $from, string $to): array
    {
        return $query
            ->whereBetween('appointment_date', [$from, $to])
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get()
            ->map(fn ($a) => [
                $a->appointment_date->format('M d, Y'),
                substr($a->appointment_time, 0, 5),
                $a->patient?->full_name ?? '-',
                $a->dentist?->name ?? '-',
                $a->service,
                ucfirst($a->status),
            ])->all();
    }
}
