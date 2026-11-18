<?php

namespace App\Http\Controllers;

use App\Support\Clinic;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        $bk = array_values(array_filter(session('appts'), fn($a) => $a['st'] === 'Booked'));
        usort($bk, fn($a, $b) => strcmp($a['date'] . $a['time'], $b['date'] . $b['time']));
        $today = date('Y-m-d');
        return view('admin.dashboard', [
            'today' => count(array_filter($bk, fn($a) => $a['date'] === $today)),
            'upcoming' => count(array_filter($bk, fn($a) => $a['date'] >= $today)),
            'inq' => count(array_filter(session('queue'), fn($q) => $q['st'] !== 'Done')),
            'next' => array_slice(array_filter($bk, fn($a) => $a['date'] >= $today), 0, 5),
        ]);
    }

    public function calendar(Request $r)
    {
        $W = Clinic::week();
        if ($r->isMethod('post')) {
            $act = $r->input('act');
            if ($act === 'book') {
                $d = $r->validate(['pat' => 'required|max:80', 'svc' => 'required|in:' . implode(',', Clinic::SERVICES), 'dr' => 'required|in:' . implode(',', Clinic::DENTISTS), 'date' => 'required|in:' . implode(',', $W), 'time' => 'required|in:' . implode(',', Clinic::SLOTS)]);
                if (Clinic::taken($d['date'], $d['time'], $d['dr'])) return back()->withErrors(['dr' => $d['dr'] . ' is already booked then.'])->withInput();
                Clinic::add($d['date'], $d['time'], $d['pat'], $d['svc'], $d['dr']);
                return redirect()->route('admin.calendar')->with('flash', 'Booked');
            }
            if (($i = Clinic::idx($r->input('id'))) !== null) {
                $appts = session('appts'); $a = $appts[$i];
                if ($act === 'cancel') { $appts[$i]['st'] = 'Cancelled'; session(['appts' => $appts]); $m = 'Booking cancelled'; }
                if ($act === 'checkin') { $q = session('queue'); $q[] = ['n' => $a['pat'], 's' => $a['svc'], 'd' => $a['dr'], 'st' => 'Waiting']; session(['queue' => $q]); $m = 'Added to queue'; }
                return redirect()->route('admin.calendar')->with('flash', $m ?? '');
            }
        }
        $sel = null;
        if ($r->query('book')) { [$sd, $st] = array_pad(explode('|', $r->query('book')), 2, ''); $sel = ['d' => $sd, 't' => $st]; }
        $v = $r->query('view') ? Clinic::idx($r->query('view')) : null;
        return view('admin.calendar', ['W' => $W, 'sel' => $sel, 'view' => $v !== null ? session('appts')[$v] : null, 'appts' => session('appts')]);
    }

    public function queue(Request $r)
    {
        if ($r->isMethod('post')) {
            $q = session('queue'); $i = (int) $r->input('i', -1); $act = $r->input('act');
            if ($act === 'call' && isset($q[$i])) $q[$i]['st'] = 'In chair';
            if ($act === 'done' && isset($q[$i])) $q[$i]['st'] = 'Done';
            if ($act === 'walkin' && trim((string) $r->input('n')) !== '') $q[] = ['n' => trim($r->input('n')), 's' => in_array($r->input('s'), Clinic::SERVICES) ? $r->input('s') : 'Consultation', 'd' => 'Next available', 'st' => 'Waiting'];
            session(['queue' => $q]);
            return redirect()->route('admin.queue');
        }
        return view('admin.queue', ['queue' => session('queue')]);
    }

    public function reminders(Request $r)
    {
        if ($r->isMethod('post')) {
            $rem = session('rem');
            if ($r->input('act') === 'all') { $rem = []; $m = 'All reminders sent'; }
            else { $i = (int) $r->input('i', -1); if (isset($rem[$i])) { $m = 'Sent to ' . $rem[$i]['n']; array_splice($rem, $i, 1); } }
            session(['rem' => $rem]);
            return redirect()->route('admin.reminders')->with('flash', $m ?? '');
        }
        return view('admin.reminders', ['rem' => session('rem')]);
    }

    public function reports()
    {
        $appts = session('appts'); $W = Clinic::week();
        $c = fn($s) => count(array_filter($appts, fn($a) => $a['st'] === $s));
        $n = array_map(fn($d) => count(array_filter($appts, fn($a) => $a['date'] === $d && $a['st'] === 'Booked')), $W);
        $live = array_filter($appts, fn($a) => $a['st'] !== 'Cancelled');
        $grp = ['Cleaning' => 0, 'Check-up' => 0, 'Filling' => 0, 'Other' => 0];
        foreach ($live as $a) {
            $s = strtolower($a['svc']);
            $k = str_contains($s, 'cleaning') ? 'Cleaning' : (str_contains($s, 'check-up') ? 'Check-up' : (str_contains($s, 'filling') ? 'Filling' : 'Other'));
            $grp[$k]++;
        }
        $tot = array_sum($grp);
        $top = array_map(fn($v) => $tot ? (int) round($v / $tot * 100) : 0, $grp);
        return view('admin.reports', ['top' => $top, 'booked' => $c('Booked'), 'done' => $c('Completed'), 'cancelled' => $c('Cancelled'), 'queueN' => count(session('queue')), 'n' => $n, 'm' => max(4, ...$n), 'W' => $W]);
    }

    public function profile(Request $r)
    {
        if ($r->isMethod('post')) {
            if ($r->has('remove')) { session(['admin.photo' => '']); return redirect()->route('admin.profile')->with('flash', 'Photo removed'); }
            $d = $r->validate(['photo' => 'nullable|mimes:jpg,jpeg,png,webp|max:2048', 'name' => 'nullable|max:80', 'role' => 'nullable|in:Front desk,Dentist', 'phone' => 'nullable|max:30', 'email' => 'nullable|email|max:120']);
            $u = session('admin');
            foreach (['name', 'role', 'phone', 'email'] as $k) if (trim((string) ($d[$k] ?? '')) !== '') $u[$k] = trim($d[$k]);
            if ($p = Clinic::photo($r->file('photo'), 'admin')) $u['photo'] = $p;
            session(['admin' => $u]);
            return redirect()->route('admin.profile')->with('flash', 'Profile saved');
        }
        return view('admin.profile', ['u' => session('admin')]);
    }
}
