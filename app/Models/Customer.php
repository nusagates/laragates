<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /**
     * ==================================================
     * MASS ASSIGNMENT
     * ==================================================
     */
    protected $fillable = [
        'name',
        'phone',

        // Contact / CRM fields
        'tags',
        'notes',

        // Compliance
        'is_blacklisted',
        'is_vip',

        // Statistics
        'total_chats',
        'total_messages',
        'last_contacted_at',

        // CRM enrichment
        'source',
        'priority',
    ];

    /**
     * ==================================================
     * CASTING
     * ==================================================
     */
    protected $casts = [
        'tags'              => 'array',
        'is_blacklisted'    => 'boolean',
        'is_vip'            => 'boolean',
        'last_contacted_at' => 'datetime',

        // CRM enrichment
        'source'            => 'string',
        'priority'          => 'string',
    ];

    /**
     * ==================================================
     * RELATIONSHIPS
     * ==================================================
     */
    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }

    /**
     * ==================================================
     * HELPER METHODS
     * ==================================================
     */

    public function isBlacklisted(): bool
    {
        return (bool) $this->is_blacklisted;
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? []);
    }

    public function addTag(string $tag): void
    {
        $tags = $this->tags ?? [];

        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->update(['tags' => $tags]);
        }
    }

    public function removeTag(string $tag): void
    {
        $tags = array_values(
            array_filter($this->tags ?? [], fn ($t) => $t !== $tag)
        );

        $this->update(['tags' => $tags]);
    }

    // Optional helpers (nice to have)
    public function isHighPriority(): bool
    {
        return $this->priority === 'high';
    }

    public function isLowPriority(): bool
    {
        return $this->priority === 'low';
    }
}
