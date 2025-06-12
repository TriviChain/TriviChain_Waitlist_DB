<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'message',
        'static_content',
        'recipients_count',
        'sent_count',
        'failed_count',
        'status',
        'sent_by',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Admin who sent this update
     */
    public function admin()
    {
        return $this->belongsTo(AdminUser::class, 'sent_by');
    }

    /**
     * Check if update is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Get success rate
     */
    public function getSuccessRate(): float
    {
        if ($this->recipients_count === 0) {
            return 0;
        }
        
        return ($this->sent_count / $this->recipients_count) * 100;
    }
}
