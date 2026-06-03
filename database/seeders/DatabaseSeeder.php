<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'Gis_Admin@GISAdmin.com')],
            [
                'name' => env('ADMIN_NAME', 'GIS_ADMIN'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'Gis@Admin!@#2026')),
                'is_admin' => true,
            ]
        );

        $this->call(ReadyNotesSeeder::class);
    }
}
