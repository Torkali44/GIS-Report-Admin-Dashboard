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
            ['email' => env('ADMIN_EMAIL', '[REDACTED_EMAIL]')],
            [
                'name' => env('ADMIN_NAME', 'GIS_ADMIN'),
                'password' => Hash::make(env('ADMIN_PASSWORD', '[REDACTED]')),
                'is_admin' => true,
            ]
        );

        $this->call(ReadyNotesSeeder::class);
    }
}
