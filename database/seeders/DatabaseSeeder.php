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
        $adminEmail = config('auth.admin.email');
        $adminName = config('auth.admin.name');
        $adminPassword = config('auth.admin.password');

        User::query()->updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'password' => Hash::make($adminPassword),
                'is_admin' => true,
            ]
        );

        $this->call(ReadyNotesSeeder::class);
    }
}
