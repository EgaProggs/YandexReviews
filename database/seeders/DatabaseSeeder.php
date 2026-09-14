<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use \Illuminate\Support\Facades\Hash;
use App\Models\Organization;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create([
            'name' => 'admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('admin'),
        ]);

        Organization::create([
            'business_id' => '201929294938',
            'yandex_url'  => 'https://yandex.ru/maps/org/rostovskiy_gosudarstvenny_tsirk/201929294938/',
            'name'        => 'Ростовский цирк',
            'parsing_status' => 'pending',
        ]);
    }
}
