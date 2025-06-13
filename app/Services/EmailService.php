<?php

namespace App\Services;

use App\Models\Waitlist;
use App\Models\EmailUpdate;
use App\Mail\WelcomeToWaitlist;
use App\Mail\WaitlistUpdate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class EmailService
{
    /**
     * Send welcome email to new waitlist member
     */
    public function sendWelcomeEmail(Waitlist $waitlistMember): bool
    {
        try {
            Mail::to($waitlistMember->email)
                ->queue(new WelcomeToWaitlist($waitlistMember));

            $waitlistMember->markWelcomeEmailSent();

            Log::info('Welcome email queued', [
                'email' => $waitlistMember->email,
                'waitlist_id' => $waitlistMember->id
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email', [
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
            $this->queueUpdateEmail($member, $emailUpdate);
        }

        return $emailUpdate;
    }

    /**
     * Queue individual update email
     */
    private function queueUpdateEmail(Waitlist $member, EmailUpdate $emailUpdate): void
    {
        Mail::to($member->email)
            ->queue(new WaitlistUpdate($member, $emailUpdate));
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
     * Process email update completion
     */
    public function markEmailUpdateCompleted(EmailUpdate $emailUpdate): void
    {
        $emailUpdate->update(['status' => 'completed']);
        
        Log::info('Email update completed', [
            'update_id' => $emailUpdate->id,
            'recipients' => $emailUpdate->recipients_count,
            'sent' => $emailUpdate->sent_count,
            'failed' => $emailUpdate->failed_count
        ]);
    }

    /**
     * Handle email send success
     */
    public function handleEmailSent(EmailUpdate $emailUpdate, Waitlist $member): void
    {
        $emailUpdate->increment('sent_count');
        $member->incrementUpdatesReceived();
    }

    /**
     * Handle email send failure
     */
    public function handleEmailFailed(EmailUpdate $emailUpdate, string $email, \Exception $exception): void
    {
        $emailUpdate->increment('failed_count');
        
        Log::error('Email send failed', [
            'update_id' => $emailUpdate->id,
            'email' => $email,
            'error' => $exception->getMessage()
        ]);
    }
}