<?php

namespace App\Http\Controllers;

use App\Support\Clinic;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function dashboard()
    {
        $up = Clinic::mine('Booked'); usort($up, fn($a, $b) => strcmp($a['date'] . $a['time'], $b['date'] . $b['time']));
        return view('patient.dashboard', ['next' => $up[0] ?? null, 'hist' => array_slice(Clinic::mine('Completed'), 0, 3)]);
    }

    public function appointments(Request $r)
    {
        if ($r->isMethod('post') && ($i = Clinic::idx($r->input('id'))) !== null) {
            $a = session('appts');
            if ($a[$i]['pat'] === session('user.name')) { $a[$i]['st'] = 'Cancelled'; session(['appts' => $a]); }
            return redirect()->route('patient.appointments')->with('flash', 'Appointment cancelled');
        }
        return view('patient.appointments', ['list' => Clinic::mine()]);
    }

    public function book(Request $r)
    {
        $edit = $r->query('edit') ? Clinic::idx($r->query('edit')) : null;
        if ($edit !== null && session('appts')[$edit]['pat'] !== session('user.name')) $edit = null;
        if ($r->isMethod('post')) {
            $d = $r->validate([
                'svc' => 'required|in:' . implode(',', Clinic::SERVICES), 'dr' => 'required|in:' . implode(',', Clinic::DENTISTS),
                'date' => 'required|date|after_or_equal:today', 'time' => 'required|in:' . implode(',', Clinic::SLOTS), 'notes' => 'nullable|max:500',
            ]);
            if (date('N', strtotime($d['date'])) > 5) return back()->withErrors(['date' => 'The clinic is closed on weekends.'])->withInput();
            $appts = session('appts');
            if (Clinic::taken($d['date'], $d['time'], $d['dr'], $edit !== null ? $appts[$edit]['id'] : 0))
                return back()->withErrors(['time' => 'That time is already taken. Please pick another.'])->withInput();
            if ($edit !== null) { $appts[$edit] = array_merge($appts[$edit], ['svc' => $d['svc'], 'dr' => $d['dr'], 'date' => $d['date'], 'time' => $d['time'], 'notes' => (string) ($d['notes'] ?? '')]); session(['appts' => $appts]); $m = 'Appointment rescheduled'; }
            else { Clinic::add($d['date'], $d['time'], session('user.name'), $d['svc'], $d['dr'], (string) ($d['notes'] ?? '')); $m = 'Appointment confirmed'; }
            return redirect()->route('patient.appointments')->with('flash', $m);
        }
        return view('patient.book', ['edit' => $edit, 'a' => $edit !== null ? session('appts')[$edit] : ['svc' => '', 'dr' => '', 'date' => '', 'time' => '', 'notes' => '']]);
    }

    public function symptoms() { return view('patient.symptoms'); }

    public function profile(Request $r)
    {
        if ($r->isMethod('post')) {
            if ($r->has('remove')) { session(['user.photo' => '']); return redirect()->route('patient.profile')->with('flash', 'Photo removed'); }
            $d = $r->validate(['photo' => 'nullable|mimes:jpg,jpeg,png,webp|max:2048', 'name' => 'nullable|max:80', 'email' => 'nullable|email|max:120', 'dob' => 'nullable|date', 'sex' => 'nullable|in:Female,Male', 'phone' => 'nullable|max:30', 'addr' => 'nullable|max:200']);
            $u = session('user'); $old = $u['name'];
            foreach (['name', 'email', 'dob', 'sex', 'phone', 'addr'] as $k) if (array_key_exists($k, $d) && ($k !== 'name' || trim((string) $d[$k]) !== '')) $u[$k] = trim((string) ($d[$k] ?? ''));
            if ($p = Clinic::photo($r->file('photo'), 'patient')) $u['photo'] = $p;
            $appts = array_map(fn($a) => $a['pat'] === $old ? array_merge($a, ['pat' => $u['name']]) : $a, session('appts'));
            session(['user' => $u, 'appts' => $appts]);
            return redirect()->route('patient.profile')->with('flash', 'Profile saved');
        }
        return view('patient.profile', ['u' => session('user')]);
    }
}
