<?php

namespace App\Services;

use App\Models\Waitlist;
use App\Models\EmailUpdate;
use App\Mail\WelcomeToWaitlist;
use App\Mail\WaitlistUpdate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
// use App\Jobs\ProcessWelcomeEmail;
use App\Jobs\ProcessWaitlistUpdate;

class EmailService
{
    /**
     * Send welcome email to new waitlist member
     */
    public function sendWelcomeEmail(Waitlist $waitlistMember): bool
    {
       try {
            // ProcessWelcomeEmail::dispatch($waitlistMember)

            Log::info('EmailService: Starting welcome email process', [
                'email' => $waitlistMember->email,
                'name' => $waitlistMember->name,
                'id' => $waitlistMember->id
            ]);

            // Validate email configuration
            $this->validateEmailConfig();

            // Send email immediately (not queued)
            Mail::to($waitlistMember->email)->send(new WelcomeToWaitlist($waitlistMember));

            Log::info('EmailService: Welcome email sent successfully', [
                'email' => $waitlistMember->email,
                'id' => $waitlistMember->id
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email', [
                'email' => $waitlistMember->email,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception('Failed to send welcome email: ' . $e->getMessage());
        }
    }

    /**
     * Validate email configuration
     */
    private function validateEmailConfig()
    {
        $configs = [
            'MAIL_HOST' => config('mail.mailers.smtp.host'),
            'MAIL_USERNAME' => config('mail.mailers.smtp.username'),
            'MAIL_PASSWORD' => config('mail.mailers.smtp.password'),
            'MAIL_FROM_ADDRESS' => config('mail.from.address'),
        ];

        foreach ($configs as $key => $value) {
            if (empty($value)) {
                throw new \Exception("Email configuration missing: {$key}");
            }
        }

        Log::info('EmailService: Email configuration validated successfully');
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