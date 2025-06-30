<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        // Create a test user if none exists
        $user = User::firstOrCreate(
            ['email' => 'ariefsushi1@gmail.com'],
            [
                'name' => 'Sushi Maru',
                'password' => bcrypt('123456789'),
            ]
        );

        // Create sample documents
        Document::factory()->count(3)->completed()->create(['user_id' => $user->id]);
        Document::factory()->count(1)->processing()->create(['user_id' => $user->id]);
        Document::factory()->count(1)->failed()->create(['user_id' => $user->id]);
    }
}