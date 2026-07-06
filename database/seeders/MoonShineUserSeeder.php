<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use MoonShine\Laravel\Models\MoonshineUser;

class MoonShineUserSeeder extends Seeder
{
    public function run(): void
    {
        MoonshineUser::factory()->create([
            'name' => 'admin@mail.ru',
            'email' => 'admin',
            'password' => bcrypt('admin'),
        ]);
    }
}
