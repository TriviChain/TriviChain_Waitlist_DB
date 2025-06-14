<?php

namespace App\Jobs;

use App\Models\Waitlist;
use App\Models\EmailUpdate;
use App\Mail\WaitlistUpdate;
use App\Services\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ProcessWaitlistUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Waitlist $waitlistMember,
        public EmailUpdate $emailUpdate,
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Mail::to($this->waitlistMember->email)
                ->send(new WaitlistUpdate($this->waitlistMember, $this->emailUpdate));

             // Create the EmailService instance
            $emailService = new EmailService();

            // Update counters
            $emailService->handleEmailSent($this->emailUpdate, $this->waitlistMember);

            Log::info('Waitlist update email sent successfully', [
                'email' => $this->waitlistMember->email,
                'update_id' => $this->emailUpdate->id,
                'subject' => $this->emailUpdate->subject
            ]);

        } catch (\Exception $e) {
            $emailService = new EmailService(); // re-instantiate to avoid null
            $emailService->handleEmailFailed($this->emailUpdate, $this->waitlistMember->email, $e);

            Log::error('Failed to send waitlist update email', [
                'email' => $this->waitlistMember->email,
                'update_id' => $this->emailUpdate->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Waitlist update email job failed permanently', [
            'email' => $this->waitlistMember->email,
            'update_id' => $this->emailUpdate->id,
            'error' => $exception->getMessage()
        ]);
    }
}
