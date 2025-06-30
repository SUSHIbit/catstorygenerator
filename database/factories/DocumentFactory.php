<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    public function definition(): array
    {
        $fileTypes = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];
        $statuses = ['uploaded', 'processing', 'completed', 'failed'];
        
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'filename' => $this->faker->word() . '.' . $this->faker->randomElement($fileTypes),
            'filepath' => 'documents/' . $this->faker->uuid() . '.pdf',
            'file_type' => $this->faker->randomElement($fileTypes),
            'file_size' => $this->faker->numberBetween(1024, 5242880), // 1KB to 5MB
            'original_content' => $this->faker->paragraphs(5, true),
            'cat_story' => $this->faker->optional(0.7)->paragraphs(3, true),
            'status' => $this->faker->randomElement($statuses),
            'error_message' => null,
            'processed_at' => $this->faker->optional(0.6)->dateTimeThisMonth(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'cat_story' => $this->faker->paragraphs(3, true),
            'processed_at' => $this->faker->dateTimeThisMonth(),
            'error_message' => null,
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'cat_story' => null,
            'processed_at' => null,
            'error_message' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'cat_story' => null,
            'processed_at' => null,
            'error_message' => $this->faker->sentence(),
        ]);
    }
}