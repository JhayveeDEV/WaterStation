<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class ProductionAdminSeeder extends Seeder
{
    public function run(): void
    {
        $name = config('services.production_admin.name');
        $email = config('services.production_admin.email');
        $password = config('services.production_admin.password');

        if (! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || ! is_string($password) || mb_strlen($password) < 16) {
            throw new RuntimeException(
                'Set a valid ADMIN_EMAIL and an ADMIN_PASSWORD of at least 16 characters in Wasmer before deploying.'
            );
        }

        $existingUser = User::query()->where('email', $email)->first();

        if ($existingUser !== null) {
            if (! $existingUser->hasRole('admin')) {
                throw new RuntimeException(
                    'ADMIN_EMAIL already belongs to a non-admin account. Choose a different ADMIN_EMAIL.'
                );
            }

            return;
        }

        $admin = User::query()->create([
            'name' => is_string($name) ? $name : 'Water Station Administrator',
            'email' => $email,
            'password' => $password,
            'status' => 'active',
        ]);

        $admin->assignRole('admin');
    }
}
