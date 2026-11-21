<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(6),
            'body' => fake()->paragraph(),
            'image_path' => null,
            'type' => fake()->randomElement([
                Announcement::TYPE_INFO,
                Announcement::TYPE_SUCCESS,
                Announcement::TYPE_WARNING,
                Announcement::TYPE_DANGER,
            ]),
            'audience' => Announcement::AUDIENCE_ALL,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the announcement is a banner with an image.
     */
    public function banner(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => Announcement::TYPE_BANNER,
            'image_path' => 'announcement-banners/test-banner.jpg',
        ]);
    }

    /**
     * Indicate that the announcement is addressed to selected users.
     */
    public function selected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'audience' => Announcement::AUDIENCE_SELECTED,
        ]);
    }
}
