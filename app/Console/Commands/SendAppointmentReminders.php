<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\ReminderService;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';

    protected $description = 'Send reminders for appointments happening tomorrow';

    public function handle(ReminderService $reminders): int
    {
        $due = Appointment::with('patient', 'dentist')
            ->where('status', 'scheduled')
            ->whereDate('appointment_date', now()->addDay()->toDateString())
            ->whereNull('reminder_sent_at')
            ->get();

        $sent = 0;

        foreach ($due as $appointment) {
            if ($reminders->send($appointment)) {
                $sent++;
            }
        }

        $this->info("Reminders sent: {$sent} of {$due->count()} due.");

        return self::SUCCESS;
    }
}