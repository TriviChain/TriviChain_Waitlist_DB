<?php

namespace App\Mail;

use App\Models\Waitlist;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

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
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->subject('Welcome to the Waitlist!')
                    ->view('emails.welcome-to-waitlist') // ensure this view exists
                    ->with([
                        'member' => $this->waitlistMember,
                    ]);
    }
}
