<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnnouncementManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_can_view_the_announcement_management_page(): void
    {
        $admin = User::factory()->admin()->create();
        Announcement::factory()->create([
            'title' => 'Jadwal stock opname',
            'body' => 'Stock opname dimulai Sabtu pagi.',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.announcements.index'))
            ->assertOk()
            ->assertSee('Pemberitahuan')
            ->assertSee('Jadwal stock opname')
            ->assertSee(route('admin.announcements.create'), false);
    }

    public function test_admins_can_view_the_announcement_forms(): void
    {
        $admin = User::factory()->admin()->create();
        $announcement = Announcement::factory()->selected()->create([
            'created_by' => $admin->id,
        ]);
        $recipient = User::factory()->create();
        $announcement->recipients()->attach($recipient);

        $this->actingAs($admin)
            ->get(route('admin.announcements.create'))
            ->assertOk()
            ->assertSee('Buat pemberitahuan')
            ->assertSee('Pilih apakah pemberitahuan dikirim ke semua user atau user tertentu saja.');

        $this->actingAs($admin)
            ->get(route('admin.announcements.edit', $announcement))
            ->assertOk()
            ->assertSee('Edit pemberitahuan')
            ->assertSee($recipient->name);
    }

    public function test_admins_can_broadcast_an_announcement_to_all_users(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.announcements.store'), [
            'title' => 'Gudang tutup lebih awal',
            'body' => 'Gudang akan tutup pukul 15.00 hari ini.',
            'type' => Announcement::TYPE_WARNING,
            'audience' => Announcement::AUDIENCE_ALL,
            'user_ids' => [$user->id],
        ]);

        $response->assertRedirect(route('admin.announcements.index'));

        $announcement = Announcement::query()->firstOrFail();

        $this->assertSame($admin->id, $announcement->created_by);
        $this->assertSame(Announcement::AUDIENCE_ALL, $announcement->audience);
        $this->assertDatabaseCount('announcement_recipients', 0);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Gudang tutup lebih awal');
    }

    public function test_admins_can_send_a_personal_announcement_to_selected_users(): void
    {
        $admin = User::factory()->admin()->create();
        $selectedUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.announcements.store'), [
            'title' => 'Akses laporan sudah aktif',
            'body' => 'Silakan buka menu laporan untuk melihat data stok.',
            'type' => Announcement::TYPE_SUCCESS,
            'audience' => Announcement::AUDIENCE_SELECTED,
            'user_ids' => [$selectedUser->id],
        ]);

        $response->assertRedirect(route('admin.announcements.index'));

        $announcement = Announcement::query()->firstOrFail();

        $this->assertSame(Announcement::AUDIENCE_SELECTED, $announcement->audience);
        $this->assertDatabaseHas('announcement_recipients', [
            'announcement_id' => $announcement->id,
            'user_id' => $selectedUser->id,
        ]);
        $this->assertDatabaseMissing('announcement_recipients', [
            'announcement_id' => $announcement->id,
            'user_id' => $otherUser->id,
        ]);

        $this->actingAs($selectedUser)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Akses laporan sudah aktif');

        $this->actingAs($otherUser)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertDontSee('Akses laporan sudah aktif');
    }

    public function test_announcements_require_a_recipient_when_the_audience_is_selected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.announcements.create'))
            ->post(route('admin.announcements.store'), [
                'title' => 'Pemberitahuan pribadi',
                'body' => 'Isi pemberitahuan.',
                'type' => Announcement::TYPE_INFO,
                'audience' => Announcement::AUDIENCE_SELECTED,
                'user_ids' => [],
            ])
            ->assertRedirect(route('admin.announcements.create'))
            ->assertSessionHasErrors('user_ids');

        $this->assertDatabaseCount('announcements', 0);
    }

    public function test_announcement_recipients_must_exist(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.announcements.create'))
            ->followingRedirects()
            ->post(route('admin.announcements.store'), [
                'title' => 'Pemberitahuan pribadi',
                'body' => 'Isi pemberitahuan.',
                'type' => Announcement::TYPE_INFO,
                'audience' => Announcement::AUDIENCE_SELECTED,
                'user_ids' => [(string) Str::uuid()],
            ])
            ->assertOk()
            ->assertSee('user_ids.0');

        $this->assertDatabaseCount('announcements', 0);
    }

    public function test_admins_can_update_announcement_recipients(): void
    {
        $admin = User::factory()->admin()->create();
        $firstRecipient = User::factory()->create();
        $secondRecipient = User::factory()->create();

        $announcement = Announcement::factory()->selected()->create([
            'title' => 'Pemberitahuan lama',
            'created_by' => $admin->id,
        ]);
        $announcement->recipients()->attach($firstRecipient);

        AnnouncementRead::create([
            'announcement_id' => $announcement->id,
            'user_id' => $firstRecipient->id,
            'read_at' => now(),
        ]);

        $response = $this->actingAs($admin)->put(route('admin.announcements.update', $announcement), [
            'title' => 'Pemberitahuan baru',
            'body' => 'Isi pemberitahuan yang sudah diperbarui.',
            'type' => Announcement::TYPE_DANGER,
            'audience' => Announcement::AUDIENCE_SELECTED,
            'user_ids' => [$secondRecipient->id],
        ]);

        $response->assertRedirect(route('admin.announcements.index'));

        $announcement->refresh();

        $this->assertSame('Pemberitahuan baru', $announcement->title);
        $this->assertSame(Announcement::TYPE_DANGER, $announcement->type);
        $this->assertDatabaseHas('announcement_recipients', [
            'announcement_id' => $announcement->id,
            'user_id' => $secondRecipient->id,
        ]);
        $this->assertDatabaseMissing('announcement_recipients', [
            'announcement_id' => $announcement->id,
            'user_id' => $firstRecipient->id,
        ]);
        $this->assertDatabaseMissing('announcement_reads', [
            'announcement_id' => $announcement->id,
            'user_id' => $firstRecipient->id,
        ]);
    }

    public function test_admins_can_delete_an_announcement_and_its_reads(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $announcement = Announcement::factory()->selected()->create([
            'created_by' => $admin->id,
        ]);
        $announcement->recipients()->attach($user);

        AnnouncementRead::create([
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
            'read_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.announcements.destroy', $announcement))
            ->assertRedirect(route('admin.announcements.index'));

        $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
        $this->assertDatabaseMissing('announcement_recipients', ['announcement_id' => $announcement->id]);
        $this->assertDatabaseMissing('announcement_reads', ['announcement_id' => $announcement->id]);
    }

    public function test_admins_can_create_a_banner_announcement_with_an_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.announcements.store'), [
            'title' => 'Promo akhir tahun',
            'type' => Announcement::TYPE_BANNER,
            'audience' => Announcement::AUDIENCE_ALL,
            'image' => UploadedFile::fake()->image('banner.jpg', 1200, 400),
        ]);

        $response->assertRedirect(route('admin.announcements.index'));

        $announcement = Announcement::query()->firstOrFail();

        $this->assertSame(Announcement::TYPE_BANNER, $announcement->type);
        $this->assertSame('', $announcement->body);
        $this->assertNotNull($announcement->image_path);
        Storage::disk('public')->assertExists($announcement->image_path);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pemberitahuan banner')
            ->assertSee('Promo akhir tahun')
            ->assertSee($announcement->imageUrl(), false);
    }

    public function test_banner_announcements_require_an_image(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('admin.announcements.create'))
            ->post(route('admin.announcements.store'), [
                'title' => 'Banner tanpa gambar',
                'type' => Announcement::TYPE_BANNER,
                'audience' => Announcement::AUDIENCE_ALL,
            ])
            ->assertRedirect(route('admin.announcements.create'))
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('announcements', 0);
    }

    public function test_banner_images_are_kept_when_updating_without_a_new_upload(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $announcement = Announcement::factory()->banner()->create([
            'created_by' => $admin->id,
        ]);
        $oldImagePath = $announcement->image_path;

        $this->actingAs($admin)
            ->put(route('admin.announcements.update', $announcement), [
                'title' => 'Banner diperbarui',
                'type' => Announcement::TYPE_BANNER,
                'audience' => Announcement::AUDIENCE_ALL,
            ])
            ->assertRedirect(route('admin.announcements.index'));

        $this->assertSame($oldImagePath, $announcement->refresh()->image_path);
    }

    public function test_changing_a_banner_to_another_type_removes_the_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $announcement = Announcement::factory()->banner()->create([
            'created_by' => $admin->id,
        ]);
        $oldImagePath = $announcement->image_path;
        Storage::disk('public')->put($oldImagePath, 'banner');

        $this->actingAs($admin)
            ->put(route('admin.announcements.update', $announcement), [
                'title' => 'Bukan banner lagi',
                'body' => 'Isi pemberitahuan biasa.',
                'type' => Announcement::TYPE_INFO,
                'audience' => Announcement::AUDIENCE_ALL,
                'image' => UploadedFile::fake()->image('replacement.jpg'),
            ])
            ->assertRedirect(route('admin.announcements.index'));

        $this->assertNull($announcement->refresh()->image_path);
        Storage::disk('public')->assertMissing($oldImagePath);
        $this->assertSame([], Storage::disk('public')->allFiles('announcement-banners'));
    }

    public function test_deleting_a_banner_removes_the_stored_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $announcement = Announcement::factory()->banner()->create([
            'created_by' => $admin->id,
        ]);
        $imagePath = $announcement->image_path;
        Storage::disk('public')->put($imagePath, 'banner');

        $this->actingAs($admin)
            ->delete(route('admin.announcements.destroy', $announcement))
            ->assertRedirect(route('admin.announcements.index'));

        $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
        Storage::disk('public')->assertMissing($imagePath);
    }

    public function test_non_admins_cannot_manage_announcements(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.announcements.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.announcements.store'), [
                'title' => 'Tidak boleh',
                'body' => 'Percobaan membuat pemberitahuan.',
                'type' => Announcement::TYPE_INFO,
                'audience' => Announcement::AUDIENCE_ALL,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('announcements', 0);
    }
}
