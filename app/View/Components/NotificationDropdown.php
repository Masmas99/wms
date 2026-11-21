<?php

namespace App\View\Components;

use App\Models\Announcement;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;

class NotificationDropdown extends Component
{
    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        $user = Auth::user();

        if ($user === null) {
            return view('components.notification-dropdown', [
                'announcements' => collect(),
                'unreadCount' => 0,
            ]);
        }

        $announcements = Announcement::query()
            ->visibleTo($user)
            ->with([
                'reads' => fn (HasMany $query) => $query->where('user_id', $user->getKey()),
            ])
            ->latest()
            ->limit(8)
            ->get();

        return view('components.notification-dropdown', [
            'announcements' => $announcements,
            'unreadCount' => Announcement::query()->visibleTo($user)->unreadBy($user)->count(),
        ]);
    }
}
