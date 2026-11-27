<?php

namespace App\Http\Controllers;

use App\Support\Clinic;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\Reminder;
use App\Models\SymptomCheck;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    public function dashboard()
    {
        $patient = Patient::where('user_id', Auth::id())->first();
        $up = $patient ? $patient->appointments()->with('dentist')->where('status', 'Booked')
            ->where(function ($query) { $query->whereDate('appointment_date', '>', today())->orWhere(function ($query) { $query->whereDate('appointment_date', today())->where('appointment_time', '>=', now()->format('H:i:s')); }); })
            ->orderBy('appointment_date')->orderBy('appointment_time')->get() : collect();
        $hist = $patient ? $patient->appointments()->with('dentist')->where('status', 'Completed')->latest('appointment_date')->take(3)->get() : collect();
        return view('patient.dashboard', ['next' => $this->appointmentRows($up)[0] ?? null, 'hist' => $this->appointmentRows($hist)]);
    }

    public function appointments(Request $r)
    {
        $patient = Patient::where('user_id', Auth::id())->firstOrFail();
        if ($r->isMethod('post')) {
            $data = $r->validate(['id' => 'required|integer']);
            $appointment = $patient->appointments()->whereKey($data['id'])->firstOrFail();
            if ($appointment->status !== 'Booked' || Carbon::parse($appointment->appointment_date->toDateString() . ' ' . $appointment->appointment_time)->isPast()) {
                return back()->withErrors(['appointment' => 'Only future booked appointments can be cancelled.']);
            }
            $appointment->update(['status' => 'Cancelled', 'slot_key' => null]);
            Reminder::where('appointment_id', $appointment->id)->whereIn('status', ['pending', 'failed', 'logged'])->update(['status' => 'cancelled']);
            AuditLog::create(['actor_id' => Auth::id(), 'action' => 'appointment.cancelled', 'subject_type' => 'Appointment', 'subject_id' => $appointment->id]);
            return redirect()->route('patient.appointments')->with('flash', 'Appointment cancelled');
        }
        foreach ($patient->appointments()->pluck('id') as $appointmentId) {
            AuditLog::create(['actor_id' => Auth::id(), 'action' => 'patient.appointment_viewed', 'subject_type' => 'Appointment', 'subject_id' => $appointmentId]);
        }
        $list = $patient->appointments()->with('dentist')->orderByDesc('appointment_date')->orderByDesc('appointment_time')->get();
        return view('patient.appointments', ['list' => $this->appointmentRows($list)]);
    }

    public function book(Request $r)
    {
        $patient = Patient::where('user_id', Auth::id())->firstOrFail();
        $dentistsQuery = User::where('role', 'admin')->where('staff_title', 'Dentist');
        if ($patient->assigned_dentist_id) $dentistsQuery->whereKey($patient->assigned_dentist_id);
        $dentists = $dentistsQuery->orderBy('name')->get(['id', 'name']);
        $editId = $r->query('edit');
        $appointment = $editId ? $patient->appointments()->whereKey($editId)->where('status', 'Booked')->first() : null;
        if ($editId && ! $appointment) return redirect()->route('patient.appointments')->withErrors(['appointment' => 'That appointment cannot be rescheduled.']);
        if ($r->isMethod('post')) {
            $d = $r->validate([
                'svc' => ['required', Rule::in(Clinic::SERVICES)],
                'dentist_id' => ['required', Rule::exists('users', 'id')->where(fn($query) => $query->where('role', 'admin')->where('staff_title', 'Dentist'))],
                'date' => 'required|date|after_or_equal:today',
                'time' => ['required', Rule::in(Clinic::SLOTS)], 'notes' => 'nullable|string|max:500',
            ]);
            if (date('N', strtotime($d['date'])) > 5) return back()->withErrors(['date' => 'The clinic is closed on weekends.'])->withInput();
            if (Carbon::parse($d['date'] . ' ' . $d['time'])->isPast()) return back()->withErrors(['time' => 'Choose a future appointment time.'])->withInput();
            $slotKey = $d['dentist_id'] . '|' . $d['date'] . '|' . $d['time'];
            if (Appointment::where('slot_key', $slotKey)->when($appointment, fn($query) => $query->whereKeyNot($appointment->id))->exists())
                return back()->withErrors(['time' => 'That time is already taken. Please pick another.'])->withInput();
            try {
                DB::transaction(function () use ($d, $patient, $appointment, $slotKey) {
                    $booking = $appointment ?: new Appointment(['patient_id' => $patient->id]);
                    $booking->fill([
                        'dentist_id' => $d['dentist_id'], 'service' => $d['svc'],
                        'appointment_date' => $d['date'], 'appointment_time' => $d['time'],
                        'status' => 'Booked', 'slot_key' => $slotKey, 'notes' => $d['notes'] ?? null,
                    ])->save();
                    $patient->assigned_dentist_id ??= $d['dentist_id'];
                    $patient->save();
                    Reminder::updateOrCreate(['appointment_id' => $booking->id, 'channel' => 'email'], ['status' => 'pending', 'scheduled_at' => Carbon::parse($d['date'] . ' ' . $d['time'])->subDay(), 'sent_at' => null, 'last_error' => null]);
                    AuditLog::create(['actor_id' => Auth::id(), 'action' => $appointment ? 'appointment.rescheduled' : 'appointment.booked', 'subject_type' => 'Appointment', 'subject_id' => $booking->id]);
                });
            } catch (QueryException $e) {
                return back()->withErrors(['time' => 'That time was just booked. Please choose another slot.'])->withInput();
            }
            return redirect()->route('patient.appointments')->with('flash', $appointment ? 'Appointment rescheduled' : 'Appointment confirmed');
        }
        $a = $appointment ? [
            'id' => $appointment->id, 'svc' => $appointment->service,
            'dr_id' => $appointment->dentist_id, 'date' => $appointment->appointment_date->toDateString(),
            'time' => substr($appointment->appointment_time, 0, 5), 'notes' => $appointment->notes,
        ] : ['id' => null, 'svc' => '', 'dr_id' => null, 'date' => '', 'time' => '', 'notes' => ''];
        return view('patient.book', ['edit' => $appointment?->id, 'a' => $a, 'dentists' => $dentists]);
    }

    public function symptoms(Request $r)
    {
        if ($r->isMethod('get')) return view('patient.symptoms');

        $data = $r->validate([
            'symptoms' => 'required|array|min:1|max:20',
            'symptoms.*' => 'required|string|max:80',
            'pain_level' => 'required|integer|min:1|max:10',
            'duration' => ['required', Rule::in(['Less than a day', '1 to 3 days', '4 to 7 days', 'More than a week'])],
            'area' => 'nullable|string|max:100', 'notes' => 'nullable|string|max:500',
        ]);

        $scores = ['Cavity' => 10, 'Gum inflammation' => 10, 'Cracked tooth' => 5, 'Other' => 8];
        $weights = [
            'Pain when chewing' => ['Cracked tooth', 15], 'Chipped tooth' => ['Cracked tooth', 25],
            'Dark spots on tooth' => ['Cavity', 20], 'Sensitivity to sweets' => ['Cavity', 20],
            'Loose tooth' => ['Gum inflammation', 15], 'Receding gums' => ['Gum inflammation', 20],
            'Pus or abscess' => ['Other', 15], 'Swelling in face' => ['Other', 10], 'Jaw pain' => ['Other', 15],
            'Mouth sores' => ['Other', 20], 'Dry mouth' => ['Other', 15], 'Fever' => ['Other', 10],
        ];
        $has = fn(string $symptom) => in_array($symptom, $data['symptoms'], true);
        if ($has('Toothache')) { $scores['Cavity'] += 25; $scores['Cracked tooth'] += 15; }
        if ($has('Sensitivity')) { $scores['Cavity'] += 25; $scores['Cracked tooth'] += 20; }
        if ($has('Swollen gums')) $scores['Gum inflammation'] += 35;
        if ($has('Bleeding')) $scores['Gum inflammation'] += 30;
        if ($has('Bad breath')) { $scores['Gum inflammation'] += 15; $scores['Cavity'] += 10; $scores['Other'] += 5; }
        $otherSymptoms = [];
        foreach ($data['symptoms'] as $symptom) {
            if (isset($weights[$symptom])) {
                [$condition, $weight] = $weights[$symptom];
                $scores[$condition] += $weight;
                if ($condition === 'Other') $otherSymptoms[] = strtolower($symptom);
            } elseif (! in_array($symptom, ['Toothache', 'Swollen gums', 'Sensitivity', 'Bleeding', 'Bad breath'], true)) {
                $scores['Other'] += 10;
                $otherSymptoms[] = strtolower($symptom);
            }
        }
        if ($data['pain_level'] >= 7) { $scores['Cracked tooth'] += 10; $scores['Cavity'] += 5; }
        if (preg_match('/bite|chew/i', $data['notes'] ?? '')) $scores['Cracked tooth'] += 20;
        if (preg_match('/gum/i', $data['area'] ?? '')) $scores['Gum inflammation'] += 15;

        $durationWeight = array_search($data['duration'], ['Less than a day', '1 to 3 days', '4 to 7 days', 'More than a week'], true);
        $urgencyScore = $data['pain_level'] + $durationWeight
            + ($has('Bleeding') ? 1 : 0) + ($has('Swollen gums') ? 2 : 0)
            + ($has('Swelling in face') ? 3 : 0) + ($has('Pus or abscess') ? 3 : 0);
        $urgency = $urgencyScore >= 12 ? 'High' : ($urgencyScore >= 8 ? 'Medium' : 'Low');
        $window = ['High' => 'Book within 24 hours', 'Medium' => 'Book within 3 days', 'Low' => 'Book within 2 weeks'][$urgency];
        arsort($scores);
        $total = array_sum($scores);
        $conditions = [];
        $topScores = array_slice($scores, 0, 3, true);
        $topTotal = array_sum($topScores);
        $assigned = 0;
        $last = array_key_last($topScores);
        foreach ($topScores as $name => $score) {
            $probability = $name === $last ? 100 - $assigned : (int) round($score / $topTotal * 100);
            $assigned += $probability;
            $conditions[] = ['name' => $name === 'Other' ? 'Other possible concern' : $name, 'probability' => $probability];
        }
        $steps = ['Cavity' => 'Book a dental exam and avoid very hot or cold food until then.', 'Gum inflammation' => 'Brush gently, floss daily and book a cleaning and gum check.', 'Cracked tooth' => 'Avoid chewing on that side and book an exam soon.', 'Other' => 'Book a consultation so a dentist can look at it.'];
        $leadingCondition = array_key_first($scores);
        $step = $steps[$leadingCondition];
        if ($urgency === 'High' && ($has('Swelling in face') || $has('Pus or abscess') || preg_match('/breath/i', $data['notes'] ?? ''))) $step .= ' If you have facial swelling or trouble breathing, seek emergency care.';
        $savedConditions = ['predictions' => $conditions, 'urgency' => $urgency, 'window' => $window];
        $patient = Patient::where('user_id', Auth::id())->firstOrFail();
        $check = $patient->symptomChecks()->create([
            'symptoms' => array_values($data['symptoms']), 'conditions' => $savedConditions,
            'area' => $data['area'] ?? null, 'pain_level' => $data['pain_level'], 'duration' => $data['duration'],
            'urgency' => $urgency, 'notes' => $data['notes'] ?? null,
        ]);
        AuditLog::create(['actor_id' => Auth::id(), 'action' => 'symptom_check.created', 'subject_type' => 'SymptomCheck', 'subject_id' => $check->id]);
        return response()->json(['c' => array_map(fn($condition) => [$condition['name'], $condition['probability']], $conditions), 'urg' => $urgency, 'win' => $window, 'step' => $step, 'disclaimer' => 'Prediction only, not a diagnosis.']);
    }

    public function profile(Request $r)
    {
        $user = Auth::user();
        $patient = Patient::where('user_id', $user->id)->firstOrFail();
        if ($r->isMethod('post')) {
            if ($r->has('remove')) {
                if ($patient->avatar_path) Storage::disk('local')->delete($patient->avatar_path);
                $patient->update(['avatar_path' => null]);
                session(['user.photo' => '']);
                return redirect()->route('patient.profile')->with('flash', 'Photo removed');
            }
            $d = $r->validate([
                'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
                'name' => 'nullable|string|max:80',
                'email' => ['nullable', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user->id)],
                'dob' => 'nullable|date',
                'sex' => ['nullable', Rule::in(['Female', 'Male'])],
                'phone' => 'nullable|string|max:30',
                'addr' => 'nullable|string|max:200',
            ]);

            if (! $patient->exists && (empty($d['dob']) || empty($d['sex']) || empty($d['phone']) || empty($d['addr']))) {
                return back()->withErrors(['dob' => 'Complete your patient details before saving your profile.'])->withInput();
            }

            $u = session('user');
            $user->update([
                'name' => trim((string) ($d['name'] ?? '')) ?: $user->name,
                'email' => strtolower(trim((string) ($d['email'] ?? ''))) ?: $user->email,
            ]);
            $patient->fill([
                'full_name' => $user->name,
                'date_of_birth' => $d['dob'] ?? $patient->date_of_birth,
                'sex' => $d['sex'] ?? $patient->sex,
                'contact_number' => $d['phone'] ?? $patient->contact_number,
                'address' => $d['addr'] ?? $patient->address,
                'email' => $user->email,
            ])->save();

            $u = array_merge($u, [
                'name' => $user->name,
                'email' => $user->email,
                'dob' => $patient->date_of_birth->format('Y-m-d'),
                'sex' => $patient->sex,
                'phone' => $patient->contact_number,
                'addr' => $patient->address,
            ]);
            if ($r->hasFile('photo')) {
                if ($patient->avatar_path) Storage::disk('local')->delete($patient->avatar_path);
                $patient->update(['avatar_path' => $r->file('photo')->store('avatars', 'local')]);
                $u['photo'] = route('patient.avatar');
            }
            session(['user' => $u]);
            AuditLog::create(['actor_id' => Auth::id(), 'action' => 'patient.profile_updated', 'subject_type' => 'Patient', 'subject_id' => $patient->id]);
            return redirect()->route('patient.profile')->with('flash', 'Profile saved');
        }
        AuditLog::create(['actor_id' => Auth::id(), 'action' => 'patient.profile_viewed', 'subject_type' => 'Patient', 'subject_id' => $patient->id]);
        return view('patient.profile', ['u' => session('user')]);
    }

    public function createPrivacyRequest(Request $r)
    {
        $data = $r->validate([
            'request_type' => ['required', Rule::in(['Correction', 'Deletion', 'Access'])],
            'message' => 'nullable|string|max:1000',
        ]);
        $patient = Patient::where('user_id', Auth::id())->firstOrFail();
        $request = $patient->privacyRequests()->create($data);
        AuditLog::create(['actor_id' => Auth::id(), 'action' => 'privacy_request.created', 'subject_type' => 'PrivacyRequest', 'subject_id' => $request->id]);
        return redirect()->route('patient.profile')->with('flash', 'Your privacy request was submitted to the clinic.');
    }

    private function appointmentRows($appointments): array
    {
        return $appointments->map(fn($appointment) => [
            'id' => $appointment->id,
            'date' => $appointment->appointment_date->toDateString(),
            'time' => substr($appointment->appointment_time, 0, 5),
            'pat' => $appointment->patient?->full_name ?? '',
            'svc' => $appointment->service,
            'dr' => $appointment->dentist?->name ?? 'Unassigned',
            'st' => $appointment->status,
            'notes' => $appointment->notes,
        ])->all();
    }
}
