<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Support\NotificationInbox;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
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

        $announcements = NotificationInbox::queryFor($user)
            ->with(NotificationInbox::relationsFor($user))
            ->when($filter === 'unread', fn (Builder $query) => $query->unreadBy($user))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $unreadCount = NotificationInbox::unreadCount($user);

        // Share the first page with the header bell so it does not repeat the queries.
        $request->attributes->set('notificationUnreadCount', $unreadCount);

        if ($filter !== 'unread' && $announcements->currentPage() === 1) {
            $request->attributes->set(
                'notificationLatestAnnouncements',
                $announcements->getCollection()->take(8),
            );
        }

        return view('pages.notifications.index', [
            'announcements' => $announcements,
            'unreadCount' => $unreadCount,
            'filter' => $filter,
        ]);
    }

    /**
     * Mark the specified announcement as read for the signed-in user.
     */
    public function markAsRead(Request $request, Announcement $announcement): JsonResponse|RedirectResponse
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

        if ($request->expectsJson()) {
            return response()->json([
                'unread_count' => NotificationInbox::unreadCount($user),
            ]);
        }

        return back();
    }

    /**
     * Mark every visible announcement as read for the signed-in user.
     */
    public function markAllAsRead(Request $request): JsonResponse|RedirectResponse
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

        if ($request->expectsJson()) {
            return response()->json(['unread_count' => 0]);
        }

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
