<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = ['type', 'name', 'created_by'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_user')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->oldest();
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function isGroup(): bool
    {
        return $this->type === 'group';
    }

    /** The title shown to a given user (group name, or the other person for a PM). */
    public function titleFor(?User $user): string
    {
        if ($this->isGroup()) {
            return $this->name ?: 'Group';
        }

        $other = $this->users->firstWhere('id', '!=', $user?->id);

        return $other?->name ?? 'Conversation';
    }

    /** Find (or create) the one-to-one conversation between two users. */
    public static function findOrCreatePm(User $a, User $b): self
    {
        $existing = self::where('type', 'pm')
            ->whereHas('users', fn ($q) => $q->where('users.id', $a->id))
            ->whereHas('users', fn ($q) => $q->where('users.id', $b->id))
            ->withCount('users')
            ->get()
            ->firstWhere('users_count', 2);

        if ($existing) {
            return $existing;
        }

        $conversation = self::create(['type' => 'pm', 'created_by' => $a->id]);
        $conversation->users()->attach([$a->id, $b->id]);

        return $conversation;
    }
}
