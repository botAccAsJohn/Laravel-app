<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'description',
        'customer_name',
        'customer_email',
        'priority',     // low, medium, high, critical
        'status',       // open, assigned, in_progress, closed
        'assigned_to',  // user_id of the admin who picked it up
    ];

    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
        ];
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
