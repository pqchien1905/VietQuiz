<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminAccountSeeder extends Seeder
{
    public function run(): void
    {
        $email = trim((string) env('ADMIN_EMAIL', ''));
        $password = (string) env('ADMIN_PASSWORD', '');

        if ($email === '' && $password === '') {
            return;
        }

        if ($email === '' || $password === '') {
            throw new RuntimeException('Set both ADMIN_EMAIL and ADMIN_PASSWORD to seed the admin account.');
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('ADMIN_EMAIL must be a valid email address.');
        }

        if ($this->isWeakPassword($password)) {
            throw new RuntimeException('ADMIN_PASSWORD is missing or too weak. Use a unique password with at least 12 characters.');
        }

        $admin = User::withTrashed()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrator',
                'email_verified_at' => now(),
                'password' => Hash::make($password),
                'role' => 'admin',
                'can_switch_role' => false,
                'last_active_role' => 'admin',
            ]
        );

        if ($admin->trashed()) {
            $admin->restore();
        }
    }

    private function isWeakPassword(string $password): bool
    {
        return strlen($password) < 12
            || in_array(strtolower($password), [
                'password',
                'password123',
                'admin',
                'admin123',
                'changeme',
                '12345678',
                '123456789',
            ], true);
    }
}
