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
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

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
        $imagePath = $data['type'] === Announcement::TYPE_BANNER
            ? $request->file('image')?->store('announcement-banners', 'public')
            : null;

        try {
            $announcement = DB::transaction(function () use ($data, $request, $imagePath): Announcement {
                $announcement = Announcement::create([
                    'title' => $data['title'],
                    'body' => $data['body'] ?? '',
                    'image_path' => $imagePath,
                    'type' => $data['type'],
                    'audience' => $data['audience'],
                    'created_by' => $request->user()->getKey(),
                ]);

                if ($data['audience'] === Announcement::AUDIENCE_SELECTED) {
                    $announcement->recipients()->sync($data['user_ids']);
                }

                return $announcement;
            });
        } catch (Throwable $exception) {
            if (filled($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $exception;
        }

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
        $oldImagePath = $announcement->image_path;
        $storedImagePath = $data['type'] === Announcement::TYPE_BANNER && $request->hasFile('image')
            ? $request->file('image')->store('announcement-banners', 'public')
            : null;
        $imagePath = $data['type'] === Announcement::TYPE_BANNER
            ? ($storedImagePath ?? $oldImagePath)
            : null;

        try {
            DB::transaction(function () use ($announcement, $data, $imagePath): void {
                $announcement->update([
                    'title' => $data['title'],
                    'body' => $data['body'] ?? '',
                    'image_path' => $imagePath,
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
        } catch (Throwable $exception) {
            if (filled($storedImagePath)) {
                Storage::disk('public')->delete($storedImagePath);
            }

            throw $exception;
        }

        if (filled($oldImagePath) && $oldImagePath !== $imagePath) {
            Storage::disk('public')->delete($oldImagePath);
        }

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
        $imagePath = $announcement->image_path;

        $announcement->delete();

        if (filled($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }

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
