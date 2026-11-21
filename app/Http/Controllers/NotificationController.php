<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Display the signed-in user's announcements.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $filter = $request->string('filter')->trim()->toString();

        $announcements = Announcement::query()
            ->visibleTo($user)
            ->with([
                'creator:id,name',
                'reads' => fn (HasMany $query) => $query->where('user_id', $user->getKey()),
            ])
            ->when($filter === 'unread', fn (Builder $query) => $query->unreadBy($user))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.notifications.index', [
            'announcements' => $announcements,
            'unreadCount' => Announcement::query()->visibleTo($user)->unreadBy($user)->count(),
            'filter' => $filter,
        ]);
    }

    /**
     * Mark the specified announcement as read for the signed-in user.
     */
    public function markAsRead(Request $request, Announcement $announcement): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            Announcement::query()->visibleTo($user)->whereKey($announcement->getKey())->exists(),
            404,
        );

        AnnouncementRead::query()->firstOrCreate(
            [
                'announcement_id' => $announcement->getKey(),
                'user_id' => $user->getKey(),
            ],
            ['read_at' => now()],
        );

        return back();
    }

    /**
     * Mark every visible announcement as read for the signed-in user.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $user = $request->user();
        $now = now();

        Announcement::query()
            ->visibleTo($user)
            ->unreadBy($user)
            ->orderBy('id')
            ->pluck('id')
            ->chunk(500)
            ->each(function (Collection $announcementIds) use ($user, $now): void {
                $rows = $announcementIds
                    ->map(fn (string $announcementId): array => [
                        'id' => (string) Str::uuid(),
                        'announcement_id' => $announcementId,
                        'user_id' => $user->getKey(),
                        'read_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->all();

                AnnouncementRead::query()->insertOrIgnore($rows);
            });

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
