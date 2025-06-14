<?php

namespace App\Services;

use App\Models\Waitlist;
use App\Models\EmailUpdate;
use App\Mail\WelcomeToWaitlist;
use App\Mail\WaitlistUpdate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessWelcomeEmail;
use App\Jobs\ProcessWaitlistUpdate;

class EmailService
{
    /**
     * Send welcome email to new waitlist member
     */
    public function sendWelcomeEmail(Waitlist $waitlistMember): bool
    {
       try {
            ProcessWelcomeEmail::dispatch($waitlistMember);

            Log::info('Welcome email job dispatched', [
                'email' => $waitlistMember->email,
                'waitlist_id' => $waitlistMember->id
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to dispatch welcome email job', [
                'email' => $waitlistMember->email,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send update to all waitlist members
     */
    public function sendWaitlistUpdate(string $subject, string $message, int $adminId): EmailUpdate
    {
        $waitlistMembers = Waitlist::all();
        
        $emailUpdate = EmailUpdate::create([
            'subject' => $subject,
            'message' => $message,
            'static_content' => $this->getStaticContent(),
            'recipients_count' => $waitlistMembers->count(),
            'sent_by' => $adminId,
            'status' => 'sending',
            'sent_at' => now(),
        ]);

        // Queue emails for bulk sending
        foreach ($waitlistMembers as $member) {
            ProcessWaitlistUpdate::dispatch($member, $emailUpdate);
        }

        Log::info('Waitlist update jobs dispatched', [
            'update_id' => $emailUpdate->id,
            'recipients' => $waitlistMembers->count(),
            'subject' => $subject
        ]);

        return $emailUpdate;
    }

    /**
     * Get static content for emails
     */
    private function getStaticContent(): string
    {
        return "
        <p>Hello from the TriviChain team!</p>
        <p>We hope you're as excited as we are about the progress we're making.</p>
        
        <!-- DYNAMIC_CONTENT_PLACEHOLDER -->
        
        <p>Thank you for being part of our journey. We can't wait to share more updates with you soon!</p>
        
        <p>Best regards,<br>
        The TriviChain Team</p>
        
        <hr>
        <p style='font-size: 12px; color: #666;'>
        You're receiving this email because you joined our waitlist. 
        If you no longer wish to receive updates, please contact us.
        </p>
        ";
    }

    /**
     * Handle email send success
     */
    public function handleEmailSent(EmailUpdate $emailUpdate, Waitlist $member): void
    {
        $emailUpdate->increment('sent_count');
        $member->incrementUpdatesReceived();

        // Check if all emails are sent
        if ($emailUpdate->sent_count + $emailUpdate->failed_count >= $emailUpdate->recipients_count) {
            $emailUpdate->update(['status' => 'completed']);
        }
    }

    /**
     * Handle email send failure
     */
    public function handleEmailFailed(EmailUpdate $emailUpdate, string $email, \Exception $exception): void
    {
        $emailUpdate->increment('failed_count');
        
        // Check if all emails are processed
        if ($emailUpdate->sent_count + $emailUpdate->failed_count >= $emailUpdate->recipients_count) {
            $emailUpdate->update(['status' => 'completed']);
        }

        Log::error('Email send failed', [
            'update_id' => $emailUpdate->id,
            'email' => $email,
            'error' => $exception->getMessage()
        ]);
    }
}