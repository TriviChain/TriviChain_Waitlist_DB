<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Waitlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'name',
        'joined_at',
        'welcome_email_sent',
        'welcome_email_sent_at',
        'updates_received',
        'last_update_received_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'welcome_email_sent' => 'boolean',
        'welcome_email_sent_at' => 'datetime',
        'last_update_received_at' => 'datetime',
    ];

    /**
     * Mark welcome email as sent
     */
    public function markWelcomeEmailSent(): void
    {
        $this->update([
            'welcome_email_sent' => true,
            'welcome_email_sent_at' => now(),
        ]);
    }

    /**
     * Increment updates received
     */
    public function incrementUpdatesReceived(): void
    {
        $this->increment('updates_received');
        $this->update(['last_update_received_at' => now()]);
    }

    /**
     * Scope for users who haven't received welcome email
     */
    public function scopeWithoutWelcomeEmail($query)
    {
        return $query->where('welcome_email_sent', false);
    }
}
