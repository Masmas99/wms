<?php

namespace App\Support;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

final class NotificationInbox
{
    /**
     * Build the base query for announcements visible to the user.
     *
     * Only fetch the columns used by the notification views.
     *
     * @return Builder<Announcement>
     */
    public static function queryFor(User $user): Builder
    {
        return Announcement::query()
            ->visibleTo($user)
            ->select(['id', 'title', 'body', 'type', 'audience', 'created_by', 'created_at']);
    }

    /**
     * Get the relationships used by the notification views.
     *
     * The read state only needs the user and announcement identifiers.
     *
     * @return array<int|string, \Closure|string>
     */
    public static function relationsFor(User $user): array
    {
        return [
            'creator:id,name',
            'reads' => function (HasMany $query) use ($user): void {
                $query
                    ->select(['id', 'announcement_id', 'user_id'])
                    ->where('user_id', $user->getKey());
            },
        ];
    }

    /**
     * Count the announcements the user has not read yet.
     */
    public static function unreadCount(User $user): int
    {
        return self::queryFor($user)->unreadBy($user)->count();
    }

    /**
     * Get the latest announcements for the notification bell.
     *
     * @return Collection<int, Announcement>
     */
    public static function latest(User $user, int $limit = 8): Collection
    {
        return self::queryFor($user)
            ->with(self::relationsFor($user))
            ->latest()
            ->limit($limit)
            ->get();
    }
}
