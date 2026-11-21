<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_only_see_broadcasts_and_their_personal_announcements(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $broadcast = Announcement::factory()->create([
            'title' => 'Informasi untuk semua user',
            'created_by' => $admin->id,
        ]);

        $personal = Announcement::factory()->selected()->create([
            'title' => 'Pemberitahuan pribadi untuk Anda',
            'created_by' => $admin->id,
        ]);
        $personal->recipients()->attach($user);

        $otherPersonal = Announcement::factory()->selected()->create([
            'title' => 'Pemberitahuan untuk user lain',
            'created_by' => $admin->id,
        ]);
        $otherPersonal->recipients()->attach($otherUser);

        $response = $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee($broadcast->title)
            ->assertSee($personal->title)
            ->assertDontSee($otherPersonal->title);

        $this->assertSame(2, $response->viewData('unreadCount'));
    }

    public function test_users_can_mark_an_announcement_as_read(): void
    {
        $user = User::factory()->create();
        $announcement = Announcement::factory()->create();

        $this->actingAs($user)
            ->from(route('notifications.index'))
            ->post(route('notifications.read', $announcement))
            ->assertRedirect(route('notifications.index'));

        $this->assertDatabaseHas('announcement_reads', [
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
        ]);

        $read = AnnouncementRead::query()->firstOrFail();
        $this->assertNotNull($read->read_at);

        $this->actingAs($user)
            ->post(route('notifications.read', $announcement))
            ->assertRedirect();

        $this->assertDatabaseCount('announcement_reads', 1);
    }

    public function test_users_can_mark_an_announcement_as_read_via_json(): void
    {
        $user = User::factory()->create();
        $announcement = Announcement::factory()->create();

        $this->actingAs($user)
            ->postJson(route('notifications.read', $announcement))
            ->assertOk()
            ->assertJson(['unread_count' => 0]);

        $this->assertDatabaseHas('announcement_reads', [
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_users_cannot_mark_another_users_personal_announcement_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $announcement = Announcement::factory()->selected()->create();
        $announcement->recipients()->attach($otherUser);

        $this->actingAs($user)
            ->post(route('notifications.read', $announcement))
            ->assertNotFound();

        $this->assertDatabaseCount('announcement_reads', 0);
    }

    public function test_users_can_mark_all_visible_announcements_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $broadcast = Announcement::factory()->create();
        $personal = Announcement::factory()->selected()->create();
        $personal->recipients()->attach($user);

        $otherPersonal = Announcement::factory()->selected()->create();
        $otherPersonal->recipients()->attach($otherUser);

        $this->actingAs($user)
            ->from(route('notifications.index'))
            ->post(route('notifications.read-all'))
            ->assertRedirect(route('notifications.index'));

        $this->assertDatabaseCount('announcement_reads', 2);
        $this->assertDatabaseHas('announcement_reads', [
            'announcement_id' => $broadcast->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('announcement_reads', [
            'announcement_id' => $personal->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('announcement_reads', [
            'announcement_id' => $otherPersonal->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_users_can_mark_all_visible_announcements_as_read_via_json(): void
    {
        $user = User::factory()->create();
        $announcement = Announcement::factory()->create();

        $this->actingAs($user)
            ->postJson(route('notifications.read-all'))
            ->assertOk()
            ->assertJson(['unread_count' => 0]);

        $this->assertDatabaseHas('announcement_reads', [
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_the_unread_filter_only_returns_unread_announcements(): void
    {
        $user = User::factory()->create();

        $unread = Announcement::factory()->create([
            'title' => 'Belum dibaca',
        ]);
        $read = Announcement::factory()->create([
            'title' => 'Sudah dibaca',
        ]);

        AnnouncementRead::create([
            'announcement_id' => $read->id,
            'user_id' => $user->id,
            'read_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('notifications.index', ['filter' => 'unread']))
            ->assertOk();

        $titles = $response->viewData('announcements')->pluck('title')->all();

        $this->assertContains($unread->title, $titles);
        $this->assertNotContains($read->title, $titles);
    }

    public function test_guests_cannot_access_notifications(): void
    {
        $this->get(route('notifications.index'))
            ->assertRedirect(route('login'));

        $this->post(route('notifications.read-all'))
            ->assertRedirect(route('login'));
    }
}
