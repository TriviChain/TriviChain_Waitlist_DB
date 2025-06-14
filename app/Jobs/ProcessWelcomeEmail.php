<?php

namespace App\Jobs;

use App\Models\Waitlist;
use App\Mail\WelcomeToWaitlist;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ProcessWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(public Waitlist $waitlistMember)
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
                ->send(new WelcomeToWaitlist($this->waitlistMember));

            $this->waitlistMember->markWelcomeEmailSent();

            Log::info('Welcome email sent successfully', [
                'email' => $this->waitlistMember->email,
                'waitlist_id' => $this->waitlistMember->id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send welcome email', [
                'email' => $this->waitlistMember->email,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Welcome email job failed permanently', [
            'email' => $this->waitlistMember->email,
            'error' => $exception->getMessage()
        ]);
    }
}
