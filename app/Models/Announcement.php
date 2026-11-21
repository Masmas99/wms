<?php

namespace App\Models;

use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;

#[Fillable(['title', 'body', 'type', 'audience', 'created_by'])]
class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory, HasUuids;

    public const AUDIENCE_ALL = 'all';

    public const AUDIENCE_SELECTED = 'selected';

    public const TYPE_INFO = 'info';

    public const TYPE_SUCCESS = 'success';

    public const TYPE_WARNING = 'warning';

    public const TYPE_DANGER = 'danger';

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_recipients')->withTimestamps();
    }

    /**
     * @return HasMany<AnnouncementRead, $this>
     */
    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    /**
     * Limit announcements to broadcasts and personal messages for the user.
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        $query->where(function (Builder $query) use ($user): void {
            $query->where('audience', self::AUDIENCE_ALL)
                ->orWhereExists(function (QueryBuilder $recipientQuery) use ($user): void {
                    $recipientQuery
                        ->selectRaw('1')
                        ->from('announcement_recipients')
                        ->whereColumn('announcement_recipients.announcement_id', 'announcements.id')
                        ->where('announcement_recipients.user_id', $user->getKey());
                });
        });
    }

    /**
     * Limit announcements that the user has not read yet.
     */
    #[Scope]
    protected function unreadBy(Builder $query, User $user): void
    {
        $query->whereDoesntHave('reads', function (Builder $readQuery) use ($user): void {
            $readQuery->where('user_id', $user->getKey());
        });
    }

    public function isReadBy(User $user): bool
    {
        if ($this->relationLoaded('reads')) {
            return $this->reads->contains(
                fn (AnnouncementRead $read): bool => $read->user_id === $user->getKey(),
            );
        }

        return $this->reads()->where('user_id', $user->getKey())->exists();
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_SUCCESS => 'Kabar baik',
            self::TYPE_WARNING => 'Peringatan',
            self::TYPE_DANGER => 'Penting',
            default => 'Informasi',
        };
    }

    public function audienceLabel(): string
    {
        return $this->audience === self::AUDIENCE_SELECTED ? 'User tertentu' : 'Semua user';
    }
}
