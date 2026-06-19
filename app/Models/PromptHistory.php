<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromptHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'prompt',
        'response',
        'model',
        'status_code',
    ];

    protected $casts = [
        'status_code' => 'integer',
    ];

    /**
     * The user who submitted this prompt (nullable for guest requests).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: only successful API calls (2xx).
     */
    public function scopeSuccessful($query)
    {
        return $query->whereBetween('status_code', [200, 299]);
    }

    /**
     * Scope: only failed API calls.
     */
    public function scopeFailed($query)
    {
        return $query->where('status_code', '>=', 400);
    }
}
