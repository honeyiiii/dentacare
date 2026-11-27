<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\PrivacyRequest;
use App\Models\QueueEntry;
use App\Models\Reminder;
use App\Models\User;
use App\Support\Clinic;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function patients(Request $r)
    {
        $search = trim((string) $r->query('search', ''));
        $query = Patient::with('assignedDentist')->orderBy('full_name');
        if (Auth::user()->staff_title === 'Dentist') $query->where('assigned_dentist_id', Auth::id());
        $patients = $query->when($search !== '', function ($query) use ($search) {
            $query->where(fn($q) => $q->where('full_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('contact_number', 'like', "%{$search}%"));
        })->paginate(15)->withQueryString();

        foreach ($patients as $patient) $this->audit('patient.viewed', 'Patient', $patient->id);
        return view('admin.patients', compact('patients', 'search'));
    }

    public function staff()
    {
        abort_unless(Auth::user()->staff_title === 'Front desk', 403);
        return view('admin.staff', ['staff' => User::where('role', 'admin')->orderBy('name')->get()]);
    }

    public function createStaff(Request $r)
    {
        abort_unless(Auth::user()->staff_title === 'Front desk', 403);
        $data = $r->validate([
            'name' => 'required|string|max:80',
            'email' => 'required|email|max:255|unique:users,email',
            'staff_title' => 'required|in:Front desk,Dentist',
        ]);
        $user = User::create([
            'name' => $data['name'], 'email' => strtolower(trim($data['email'])),
            'password' => Str::random(64), 'role' => 'admin', 'staff_title' => $data['staff_title'],
        ]);
        $status = Password::sendResetLink(['email' => $user->email]);
        $this->audit('staff.created', 'User', $user->id);
        $message = $status !== Password::RESET_LINK_SENT
            ? 'Staff account created. The staff member can request a password setup link from Sign in.'
            : (in_array(config('mail.default'), ['log', 'array'], true)
                ? 'Staff account created. The development mailer recorded the setup email but did not deliver it; check storage/logs/laravel.log or configure SMTP.'
                : 'Staff account created. The password setup email was handed to the configured mail service.');
        return redirect()->route('admin.staff')->with('flash', $message);
    }

    public function dashboard()
    {
        $today = today()->toDateString();
        $query = Appointment::query();
        if (Auth::user()->staff_title === 'Dentist') $query->where('dentist_id', Auth::id());
        $todayCount = (clone $query)->whereDate('appointment_date', $today)->where('status', 'Booked')->count();
        $upcoming = (clone $query)->where('appointment_date', '>=', $today)->where('status', 'Booked')->count();
        $queueQuery = QueueEntry::whereDate('queue_date', $today);
        if (Auth::user()->staff_title === 'Dentist') $queueQuery->where('dentist_id', Auth::id());
        $inq = (clone $queueQuery)->whereIn('status', ['Waiting', 'In chair'])->count();
        $reminders = Reminder::whereIn('status', ['pending', 'failed', 'logged'])->when(Auth::user()->staff_title === 'Dentist', fn($q) => $q->whereHas('appointment', fn($a) => $a->where('dentist_id', Auth::id())))->count();
        $next = (clone $query)->with(['patient', 'dentist'])->where('appointment_date', '>=', $today)
            ->where('status', 'Booked')->orderBy('appointment_date')->orderBy('appointment_time')->take(5)->get();
        foreach ($next as $appointment) $this->audit('appointment.viewed_by_staff', 'Appointment', $appointment->id);
        return view('admin.dashboard', [
            'today' => $todayCount, 'upcoming' => $upcoming, 'inq' => $inq, 'reminders' => $reminders,
            'next' => $next->map(fn($a) => $this->appointmentRow($a))->all(),
        ]);
    }

    public function calendar(Request $r)
    {
        $week = Clinic::week();
        $dentists = User::where('role', 'admin')->where('staff_title', 'Dentist')->orderBy('name')->get(['id', 'name']);
        $patientsQuery = Patient::orderBy('full_name');
        if (Auth::user()->staff_title === 'Dentist') $patientsQuery->where('assigned_dentist_id', Auth::id());
        $patients = $patientsQuery->get(['id', 'full_name']);
        foreach ($patients as $patient) $this->audit('patient.viewed', 'Patient', $patient->id);

        if ($r->isMethod('post')) {
            if ($r->input('act') === 'book') {
                $data = $r->validate([
                    'patient_id' => ['required', 'integer', Rule::exists('patients', 'id')],
                    'service' => ['required', Rule::in(Clinic::SERVICES)],
                    'dentist_id' => ['required', Rule::exists('users', 'id')->where(fn($q) => $q->where('role', 'admin')->where('staff_title', 'Dentist'))],
                    'date' => ['required', Rule::in($week)], 'time' => ['required', Rule::in(Clinic::SLOTS)],
                ]);
                $patient = $patientsQuery->whereKey($data['patient_id'])->first();
                if (! $patient) return back()->withErrors(['patient_id' => 'That patient is not assigned to your clinic view.'])->withInput();
                if (Auth::user()->staff_title === 'Dentist' && (int) $data['dentist_id'] !== (int) Auth::id()) {
                    return back()->withErrors(['dentist_id' => 'Dentists can only book appointments under their own account.'])->withInput();
                }
                if (Carbon::parse($data['date'] . ' ' . $data['time'])->isPast()) return back()->withErrors(['time' => 'Choose a future time.'])->withInput();
                $slotKey = $data['dentist_id'] . '|' . $data['date'] . '|' . $data['time'];
                try {
                    DB::transaction(function () use ($data, $patient, $slotKey) {
                        $appointment = Appointment::create([
                            'patient_id' => $patient->id, 'dentist_id' => $data['dentist_id'],
                            'service' => $data['service'], 'appointment_date' => $data['date'],
                            'appointment_time' => $data['time'], 'status' => 'Booked', 'slot_key' => $slotKey,
                        ]);
                        $patient->assigned_dentist_id ??= $data['dentist_id'];
                        $patient->save();
                        Reminder::firstOrCreate(
                            ['appointment_id' => $appointment->id, 'channel' => 'email'],
                            ['status' => 'pending', 'scheduled_at' => Carbon::parse($data['date'] . ' ' . $data['time'])->subDay()],
                        );
                        $this->audit('appointment.booked_by_staff', 'Appointment', $appointment->id);
                    });
                } catch (QueryException $e) {
                    return back()->withErrors(['time' => 'That dentist and time are already booked.'])->withInput();
                }
                return redirect()->route('admin.calendar')->with('flash', 'Appointment booked');
            }

            $data = $r->validate(['id' => 'required|integer', 'act' => ['required', Rule::in(['checkin', 'cancel'])]]);
            $appointment = $this->staffAppointments()->whereKey($data['id'])->firstOrFail();
            if ($appointment->status !== 'Booked') return back()->withErrors(['appointment' => 'Only booked appointments can be changed.']);

            if ($data['act'] === 'cancel') {
                if (QueueEntry::where('appointment_id', $appointment->id)->whereIn('status', ['Waiting', 'In chair'])->exists()) {
                    return back()->withErrors(['appointment' => 'Remove this patient from the active queue before cancelling the appointment.']);
                }
                $appointment->update(['status' => 'Cancelled', 'slot_key' => null]);
                Reminder::where('appointment_id', $appointment->id)->whereIn('status', ['pending', 'failed', 'logged'])->update(['status' => 'cancelled']);
                $this->audit('appointment.cancelled_by_staff', 'Appointment', $appointment->id);
                return redirect()->route('admin.calendar')->with('flash', 'Booking cancelled');
            }

            if (! $appointment->appointment_date->isToday()) return back()->withErrors(['appointment' => 'Only today’s appointments can be checked in.']);
            try {
                $queueEntry = QueueEntry::firstOrCreate(
                ['appointment_id' => $appointment->id],
                [
                    'patient_id' => $appointment->patient_id, 'dentist_id' => $appointment->dentist_id,
                    'patient_name' => $appointment->patient->full_name, 'service' => $appointment->service,
                    'queue_date' => today(), 'position' => (QueueEntry::whereDate('queue_date', today())->max('position') ?? 0) + 1,
                    'status' => 'Waiting',
                ],
                );
            } catch (QueryException $e) {
                $queueEntry = QueueEntry::where('appointment_id', $appointment->id)->first();
                if (! $queueEntry) return back()->withErrors(['queue' => 'The queue changed just now. Please retry check-in.']);
            }
            $this->audit('appointment.checked_in', 'Appointment', $appointment->id);
            return redirect()->route('admin.queue')->with('flash', $queueEntry->wasRecentlyCreated ? 'Patient added to queue' : 'Patient is already in the queue');
        }

        $book = $r->query('book') ? array_pad(explode('|', (string) $r->query('book')), 2, '') : null;
        $sel = $book && in_array($book[0], $week, true) && in_array($book[1], Clinic::SLOTS, true) ? ['d' => $book[0], 't' => $book[1]] : null;
        $view = $r->query('view') ? $this->staffAppointments()->with(['patient', 'dentist'])->find($r->integer('view')) : null;
        if ($view) $this->audit('appointment.viewed_by_staff', 'Appointment', $view->id);
        $query = $this->staffAppointments()->with(['patient', 'dentist'])->whereIn('appointment_date', $week);
        $appointmentRecords = $query->get();
        foreach ($appointmentRecords as $appointment) $this->audit('appointment.viewed_by_staff', 'Appointment', $appointment->id);
        $appointments = $appointmentRecords->map(fn($a) => $this->appointmentRow($a))->all();
        return view('admin.calendar', ['W' => $week, 'sel' => $sel, 'view' => $view ? $this->appointmentRow($view) : null, 'appts' => $appointments, 'dentists' => $dentists, 'patients' => $patients]);
    }

    public function queue(Request $r)
    {
        if ($r->isMethod('post')) {
            $action = $r->input('act');
            if ($action === 'walkin') {
                $data = $r->validate([
                    'n' => 'required|string|max:100', 's' => ['required', Rule::in(Clinic::SERVICES)],
                    'dentist_id' => ['nullable', Rule::exists('users', 'id')->where(fn($q) => $q->where('role', 'admin')->where('staff_title', 'Dentist'))],
                ]);
                $dentistId = Auth::user()->staff_title === 'Dentist' ? Auth::id() : ($data['dentist_id'] ?? null);
                try {
                    QueueEntry::create([
                    'patient_name' => trim($data['n']), 'service' => $data['s'], 'dentist_id' => $dentistId,
                    'queue_date' => today(), 'position' => (QueueEntry::whereDate('queue_date', today())->max('position') ?? 0) + 1,
                    'status' => 'Waiting',
                    ]);
                } catch (QueryException $e) {
                    return back()->withErrors(['queue' => 'The queue changed just now. Please try adding the walk-in again.']);
                }
                $this->audit('queue.walkin_added', 'QueueEntry', null);
                return redirect()->route('admin.queue')->with('flash', 'Walk-in added to queue');
            }

            $data = $r->validate(['id' => 'required|integer', 'act' => ['required', Rule::in(['call', 'done'])]]);
            $entry = $this->staffQueue()->whereKey($data['id'])->firstOrFail();
            if ($data['act'] === 'call') {
                if ($entry->status !== 'Waiting') return back()->withErrors(['queue' => 'Only waiting patients can be called.']);
                $firstWaiting = $this->staffQueue()->whereDate('queue_date', today())->where('status', 'Waiting')->orderBy('position')->first();
                if ($firstWaiting?->id !== $entry->id) return back()->withErrors(['queue' => 'Call patients in queue order.']);
                $dentistId = $entry->dentist_id ?: (Auth::user()->staff_title === 'Dentist' ? Auth::id() : User::where('role', 'admin')->where('staff_title', 'Dentist')->value('id'));
                if (! $dentistId) return back()->withErrors(['queue' => 'Assign a dentist before calling this patient.']);
                try {
                    $entry->update(['dentist_id' => $dentistId, 'status' => 'In chair', 'chair_key' => (string) $dentistId]);
                } catch (QueryException $e) {
                    return back()->withErrors(['queue' => 'That dentist already has a patient in the chair.']);
                }
                $this->audit('queue.patient_called', 'QueueEntry', $entry->id);
            } else {
                if ($entry->status !== 'In chair') return back()->withErrors(['queue' => 'Only a patient in the chair can be marked done.']);
                DB::transaction(function () use ($entry) {
                    $entry->update(['status' => 'Done', 'chair_key' => null]);
                    if ($entry->appointment && $entry->appointment->status === 'Booked') {
                        $entry->appointment->update(['status' => 'Completed']);
                    }
                });
                $this->audit('queue.patient_completed', 'QueueEntry', $entry->id);
            }
            return redirect()->route('admin.queue');
        }

        $queue = $this->staffQueue()->whereDate('queue_date', today())->orderBy('position')->get()
            ->map(fn($q) => ['id' => $q->id, 'n' => $q->patient_name, 's' => $q->service, 'd' => $q->dentist?->name ?? 'Unassigned', 'st' => $q->status])->all();
        foreach ($queue as $entry) $this->audit('queue.entry_viewed', 'QueueEntry', $entry['id']);
        $dentists = User::where('role', 'admin')->where('staff_title', 'Dentist')->orderBy('name')->get(['id', 'name']);
        return view('admin.queue', compact('queue', 'dentists'));
    }

    public function reminders(Request $r)
    {
        if ($r->isMethod('post')) {
            $data = $r->validate(['id' => 'required|integer']);
            $reminder = Reminder::with('appointment.patient.user')->findOrFail($data['id']);
            if (Auth::user()->staff_title === 'Dentist' && $reminder->appointment->dentist_id !== Auth::id()) abort(403);
            if ($reminder->status === 'sent') return back()->with('flash', 'This reminder has already been sent.');
            if (! in_array($reminder->status, ['pending', 'failed', 'logged'], true) || $reminder->appointment->status !== 'Booked') {
                $reminder->update(['status' => 'cancelled']);
                return back()->withErrors(['reminder' => 'This appointment is no longer booked, so its reminder was not sent.']);
            }
            $email = $reminder->appointment->patient->user?->email ?: $reminder->appointment->patient->email;
            if (! $email) {
                $reminder->update(['status' => 'failed', 'last_error' => 'Patient has no email address.']);
                return back()->withErrors(['reminder' => 'No email address is saved for this patient.']);
            }
            try {
                $appointment = $reminder->appointment;
                Mail::raw('Reminder: ' . $appointment->service . ' with ' . $appointment->dentist->name . ' on ' . $appointment->appointment_date->format('M j, Y') . ' at ' . substr($appointment->appointment_time, 0, 5) . '.', function ($message) use ($email) {
                    $message->to($email)->subject('DentaCare appointment reminder');
                });
                $mailer = config('mail.default');
                if (in_array($mailer, ['log', 'array'], true)) {
                    $reminder->update(['status' => 'logged', 'sent_at' => null, 'last_error' => null]);
                    $this->audit('reminder.email_logged', 'Reminder', $reminder->id);
                    return back()->with('flash', 'Email was recorded by the development mailer, not delivered. Configure a real mail service to deliver it.');
                }
                $reminder->update(['status' => 'sent', 'sent_at' => now(), 'last_error' => null]);
                $this->audit('reminder.email_sent', 'Reminder', $reminder->id);
                return back()->with('flash', 'Reminder handed to the configured mail service.');
            } catch (\Throwable $e) {
                $reminder->update(['status' => 'failed', 'last_error' => 'Email delivery failed. Try again or contact support.']);
                $this->audit('reminder.email_failed', 'Reminder', $reminder->id);
                return back()->withErrors(['reminder' => 'Email delivery failed. The reminder was not marked as sent.']);
            }
        }

        $query = Reminder::with('appointment.patient')->whereIn('status', ['pending', 'failed', 'logged'])->orderBy('scheduled_at');
        if (Auth::user()->staff_title === 'Dentist') $query->whereHas('appointment', fn($q) => $q->where('dentist_id', Auth::id()));
        $rem = $query->get()->map(fn($item) => [
            'id' => $item->id,
            'n' => $item->appointment->patient->full_name,
            'm' => $item->appointment->service . ' on ' . $item->appointment->appointment_date->format('M j, Y') . ' at ' . substr($item->appointment->appointment_time, 0, 5),
            't' => strtoupper($item->channel), 'status' => $item->status,
        ])->all();
        foreach ($rem as $item) $this->audit('reminder.viewed', 'Reminder', $item['id']);
        return view('admin.reminders', compact('rem'));
    }

    public function reports(Request $r)
    {
        [$from, $to] = $this->reportRange($r);
        $query = $this->staffAppointments()->whereBetween('appointment_date', [$from, $to]);
        $booked = (clone $query)->where('status', 'Booked')->count();
        $done = (clone $query)->where('status', 'Completed')->count();
        $cancelled = (clone $query)->where('status', 'Cancelled')->count();
        $queueQuery = $this->staffQueue()->whereBetween('queue_date', [$from, $to]);
        $queueN = (clone $queueQuery)->count();
        $days = Carbon::parse($from)->daysUntil(Carbon::parse($to)->addDay());
        $W = collect($days)->map(fn($date) => $date->toDateString())->all();
        $n = array_map(fn($date) => (clone $query)->whereDate('appointment_date', $date)->where('status', 'Booked')->count(), $W);
        $m = max(4, ...$n);
        $services = (clone $query)->where('status', '!=', 'Cancelled')->select('service', DB::raw('count(*) as total'))->groupBy('service')->pluck('total', 'service')->all();
        $tot = max(1, array_sum($services));
        $top = array_map(fn($v) => (int) round($v / $tot * 100), $services);
        return view('admin.reports', compact('booked', 'done', 'cancelled', 'queueN', 'W', 'n', 'm', 'top', 'from', 'to'));
    }

    public function exportReports(Request $r)
    {
        [$from, $to] = $this->reportRange($r);
        $rows = $this->staffAppointments()->with(['patient', 'dentist'])->whereBetween('appointment_date', [$from, $to])->orderBy('appointment_date')->orderBy('appointment_time')->get();
        $this->audit('reports.exported', 'Appointment', null);
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Time', 'Patient ID', 'Service', 'Dentist ID', 'Status']);
            foreach ($rows as $a) fputcsv($out, [$a->appointment_date->toDateString(), substr($a->appointment_time, 0, 5), $a->patient_id, $a->service, $a->dentist_id, $a->status]);
            fclose($out);
        }, 'dentacare-report.csv', ['Content-Type' => 'text/csv']);
    }

    public function privacyRequests(Request $r)
    {
        $query = PrivacyRequest::with('patient')->latest();
        if (Auth::user()->staff_title === 'Dentist') $query->whereHas('patient', fn($q) => $q->where('assigned_dentist_id', Auth::id()));
        $requests = $query->paginate(20);
        foreach ($requests as $request) $this->audit('privacy_request.viewed', 'PrivacyRequest', $request->id);
        return view('admin.privacy-requests', compact('requests'));
    }

    public function updatePrivacyRequest(Request $r, int $id)
    {
        $data = $r->validate(['status' => ['required', Rule::in(['In review', 'Completed', 'Declined'])]]);
        $request = PrivacyRequest::with('patient')->findOrFail($id);
        if (Auth::user()->staff_title === 'Dentist' && $request->patient->assigned_dentist_id !== Auth::id()) abort(403);
        if ($request->request_type === 'Deletion' && $data['status'] === 'Completed') {
            abort_unless(Auth::user()->staff_title === 'Front desk', 403);
            DB::transaction(function () use ($request) {
                $patient = $request->patient;
                if (! $patient->trashed()) {
                    if ($patient->avatar_path) Storage::disk('local')->delete($patient->avatar_path);
                    $user = $patient->user;
                    $patient->appointments()->update(['notes' => null]);
                    Reminder::whereIn('appointment_id', $patient->appointments()->select('id'))
                        ->whereIn('status', ['pending', 'failed', 'logged'])->update(['status' => 'cancelled']);
                    QueueEntry::where('patient_id', $patient->id)->update(['patient_id' => null, 'patient_name' => 'Deleted patient']);
                    $patient->symptomChecks()->delete();
                    $patient->update([
                        'full_name' => 'Deleted patient', 'email' => null, 'contact_number' => 'Deleted',
                        'address' => 'Deleted', 'date_of_birth' => '1900-01-01', 'sex' => 'Not specified',
                        'avatar_path' => null, 'assigned_dentist_id' => null,
                    ]);
                    $patient->delete();
                    if ($user) $user->forceDelete();
                }
                $request->update(['status' => 'Completed', 'message' => null, 'handled_by' => Auth::id(), 'handled_at' => now()]);
                $this->audit('privacy_request.deletion_processed', 'PrivacyRequest', $request->id);
            });
            return back()->with('flash', 'Deletion request processed; the patient account was removed and the patient profile was anonymized.');
        }
        $request->update(['status' => $data['status'], 'handled_by' => Auth::id(), 'handled_at' => now()]);
        $this->audit('privacy_request.updated', 'PrivacyRequest', $request->id);
        return back()->with('flash', 'Privacy request updated.');
    }

    public function profile(Request $r)
    {
        $user = Auth::user();
        if ($r->isMethod('post')) {
            if ($r->has('remove')) {
                if ($user->avatar_path) Storage::disk('local')->delete($user->avatar_path);
                $user->update(['avatar_path' => null]);
                session(['admin.photo' => '']);
                return redirect()->route('admin.profile')->with('flash', 'Photo removed');
            }
            $data = $r->validate([
                'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:2048', 'name' => 'nullable|string|max:80',
                'phone' => 'nullable|string|max:30',
                'email' => ['nullable', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user->id)],
            ]);
            $user->update([
                'name' => trim((string) ($data['name'] ?? '')) ?: $user->name,
                'email' => strtolower(trim((string) ($data['email'] ?? ''))) ?: $user->email,
                'phone' => $data['phone'] ?? $user->phone,
            ]);
            if ($r->hasFile('photo')) {
                if ($user->avatar_path) Storage::disk('local')->delete($user->avatar_path);
                $user->update(['avatar_path' => $r->file('photo')->store('avatars', 'local')]);
            }
            $this->audit('staff.profile_updated', 'User', $user->id);
            session(['admin' => ['name' => $user->name, 'role' => $user->staff_title, 'email' => $user->email, 'phone' => $user->phone, 'photo' => $user->avatar_path ? route('admin.avatar') : '']]);
            return redirect()->route('admin.profile')->with('flash', 'Profile saved');
        }
        return view('admin.profile', ['u' => ['name' => $user->name, 'role' => $user->staff_title, 'email' => $user->email, 'phone' => $user->phone, 'photo' => $user->avatar_path ? route('admin.avatar') : '']]);
    }

    private function reportRange(Request $r): array
    {
        $defaultFrom = now()->startOfWeek()->toDateString();
        $defaultTo = now()->endOfWeek()->toDateString();
        $data = $r->validate(['from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from']);
        $from = $data['from'] ?? $defaultFrom;
        $to = $data['to'] ?? $defaultTo;
        if (Carbon::parse($from)->diffInDays(Carbon::parse($to)) > 366) {
            abort(422, 'Report date range cannot exceed 366 days.');
        }
        return [$from, $to];
    }

    private function staffAppointments()
    {
        $query = Appointment::query();
        if (Auth::user()->staff_title === 'Dentist') $query->where('dentist_id', Auth::id());
        return $query;
    }

    private function staffQueue()
    {
        $query = QueueEntry::query();
        if (Auth::user()->staff_title === 'Dentist') $query->where('dentist_id', Auth::id());
        return $query;
    }

    private function appointmentRow(Appointment $a): array
    {
        $a->loadMissing(['patient', 'dentist']);
        return [
            'id' => $a->id, 'date' => $a->appointment_date->toDateString(), 'time' => substr($a->appointment_time, 0, 5),
            'pat' => $a->patient?->full_name ?? '', 'pat_id' => $a->patient_id, 'svc' => $a->service,
            'dr' => $a->dentist?->name ?? '', 'st' => $a->status, 'notes' => $a->notes,
        ];
    }

    private function audit(string $action, ?string $type = null, ?int $id = null): void
    {
        AuditLog::create(['actor_id' => Auth::id(), 'action' => $action, 'subject_type' => $type, 'subject_id' => $id]);
    }
}
