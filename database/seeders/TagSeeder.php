<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            ['name' => 'urgent', 'colour' => '#ff0000'],
            ['name' => 'bug', 'colour' => '#ff6b6b'],
            ['name' => 'feature', 'colour' => '#4ecdc4'],
            ['name' => 'question', 'colour' => '#ffe66d'],
            ['name' => 'support', 'colour' => '#95e1d3'],
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate(
                ['name' => $tag['name']],
                ['colour' => $tag['colour']]
            );
        }
    }
}
