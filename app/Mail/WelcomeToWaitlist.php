<?php

namespace App\Mail;

use App\Models\Waitlist;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WelcomeToWaitlist extends Mailable
{
    use Queueable, SerializesModels;

    public Waitlist $waitlistMember;

    /**
     * Create a new message instance.
     */
    public function __construct(Waitlist $waitlistMember)
    {
        $this->waitlistMember = $waitlistMember;

        Log::info('WelcomeToWaitlist: Mailable instance created', [
            'email' => $waitlistMember->email,
            'name' => $waitlistMember->name,
            'id' => $waitlistMember->id
        ]);
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        Log::info('WelcomeToWaitlist: Building email message');

        return $this->subject('Welcome to Trivichain Waitlist! 🎉!')
                    ->view('emails.welcome-waitlist') // ensure this view exists
                    ->text('emails.welcome-waitlist-text') // Add text version
                    ->with([
                        'member' => $this->waitlistMember,
                        // Add variables that our template expects
                        'name' => $this->waitlistMember->name ?: 'there',
                        'email' => $this->waitlistMember->email,
                        'joinedAt' => $this->waitlistMember->joined_at,
                        'waitlistId' => $this->waitlistMember->id,
                    ]);
    }
}
