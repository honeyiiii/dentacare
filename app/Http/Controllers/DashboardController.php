<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Patient dashboard
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'patient') {

            $mine = Appointment::whereHas(
                'patient',
                fn ($p) => $p->where('user_id', $user->id)
            );

            $stats = [
                'Upcoming appointments' => (clone $mine)
                    ->where('status', 'scheduled')
                    ->whereDate('appointment_date', '>=', today())
                    ->count(),

                'Cancelled appointments' => (clone $mine)
                    ->where('status', 'cancelled')
                    ->count(),
            ];

            $appointments = (clone $mine)
                ->with(['patient', 'dentist'])
                ->orderBy('appointment_date')
                ->orderBy('appointment_time')
                ->limit(10)
                ->get();

        } else {

            /*
            |--------------------------------------------------------------------------
            | Staff / Owner dashboard
            |--------------------------------------------------------------------------
            */

            $stats = [
                'Total patients' => Patient::count(),

                'Appointments today' => Appointment::whereDate(
                    'appointment_date',
                    today()
                )
                    ->where('status', '!=', 'cancelled')
                    ->count(),

                'Scheduled appointments' => Appointment::where(
                    'status',
                    'scheduled'
                )->count(),

                'Cancelled appointments' => Appointment::where(
                    'status',
                    'cancelled'
                )->count(),

                'Active dentists' => User::where(
                    'role',
                    'dentist'
                )
                    ->where('is_active', true)
                    ->count(),
            ];

            /*
            |--------------------------------------------------------------------------
            | Recent appointments for owner/staff
            |--------------------------------------------------------------------------
            */

            $appointments = Appointment::with([
                'patient',
                'dentist',
            ])
                ->orderBy('appointment_date')
                ->orderBy('appointment_time')
                ->limit(10)
                ->get();
        }

        return view('dashboard', [
            'stats' => $stats,
            'user' => $user,
            'appointments' => $appointments,
        ]);
    }
}