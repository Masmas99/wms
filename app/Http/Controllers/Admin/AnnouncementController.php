<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAnnouncementRequest;
use App\Http\Requests\Admin\UpdateAnnouncementRequest;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of the announcements.
     */
    public function index(): View
    {
        return view('pages.admin.announcements.index', [
            'announcements' => Announcement::query()
                ->with('creator:id,name')
                ->withCount(['reads', 'recipients'])
                ->latest()
                ->paginate(15),
            'userCount' => User::query()->count(),
        ]);
    }

    /**
     * Show the form for creating a new announcement.
     */
    public function create(): View
    {
        return view('pages.admin.announcements.create', [
            'announcement' => new Announcement([
                'type' => Announcement::TYPE_INFO,
                'audience' => Announcement::AUDIENCE_ALL,
            ]),
            'users' => $this->userOptions(),
            'selectedUserIds' => [],
        ]);
    }

    /**
     * Store a newly created announcement.
     */
    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $announcement = DB::transaction(function () use ($data, $request): Announcement {
            $announcement = Announcement::create([
                'title' => $data['title'],
                'body' => $data['body'],
                'type' => $data['type'],
                'audience' => $data['audience'],
                'created_by' => $request->user()->getKey(),
            ]);

            if ($data['audience'] === Announcement::AUDIENCE_SELECTED) {
                $announcement->recipients()->sync($data['user_ids']);
            }

            return $announcement;
        });

        return redirect()
            ->route('admin.announcements.index')
            ->with('popup', [
                'type' => 'success',
                'title' => 'Pemberitahuan terkirim',
                'message' => $announcement->audience === Announcement::AUDIENCE_ALL
                    ? 'Pemberitahuan "'.$announcement->title.'" dikirim ke semua user.'
                    : 'Pemberitahuan "'.$announcement->title.'" dikirim ke '.count($data['user_ids']).' user.',
            ]);
    }

    /**
     * Show the form for editing the specified announcement.
     */
    public function edit(Announcement $announcement): View
    {
        $announcement->load('recipients:id');

        return view('pages.admin.announcements.edit', [
            'announcement' => $announcement,
            'users' => $this->userOptions(),
            'selectedUserIds' => $announcement->recipients->pluck('id')->all(),
        ]);
    }

    /**
     * Update the specified announcement.
     */
    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($announcement, $data): void {
            $announcement->update([
                'title' => $data['title'],
                'body' => $data['body'],
                'type' => $data['type'],
                'audience' => $data['audience'],
            ]);

            if ($data['audience'] === Announcement::AUDIENCE_SELECTED) {
                $announcement->recipients()->sync($data['user_ids']);
                $announcement->reads()->whereNotIn('user_id', $data['user_ids'])->delete();
            } else {
                $announcement->recipients()->detach();
            }
        });

        return redirect()
            ->route('admin.announcements.index')
            ->with('popup', [
                'type' => 'success',
                'title' => 'Pemberitahuan diperbarui',
                'message' => 'Perubahan pada pemberitahuan "'.$announcement->title.'" telah disimpan.',
            ]);
    }

    /**
     * Remove the specified announcement.
     */
    public function destroy(Announcement $announcement): RedirectResponse
    {
        $title = $announcement->title;

        $announcement->delete();

        return redirect()
            ->route('admin.announcements.index')
            ->with('popup', [
                'type' => 'success',
                'title' => 'Pemberitahuan dihapus',
                'message' => 'Pemberitahuan "'.$title.'" telah dihapus.',
            ]);
    }

    /**
     * Get the selectable announcement recipients.
     *
     * @return Collection<int, array{id: string, name: string, email: string, role: string}>
     */
    private function userOptions(): Collection
    {
        return User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]);
    }
}
