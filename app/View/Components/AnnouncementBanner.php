<?php

namespace App\View\Components;

use App\Models\Announcement;
use App\Support\NotificationInbox;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class AnnouncementBanner extends Component
{
    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $user = Auth::user();

        if ($user === null || $user->isAdmin()) {
            return '';
        }

        $announcement = NotificationInbox::queryFor($user)
            ->where('type', Announcement::TYPE_BANNER)
            ->whereNotNull('image_path')
            ->unreadBy($user)
            ->latest()
            ->first();

        if ($announcement === null) {
            return '';
        }

        return view('components.announcement-banner', [
            'announcement' => $announcement,
        ]);
    }
}
