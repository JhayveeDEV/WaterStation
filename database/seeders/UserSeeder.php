<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/** One demo login per role so the client can explore the whole system immediately after install. */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->make('Aira Santos', 'admin@waterstation.test', 'admin');
        $this->make('Marco Reyes', 'staff@waterstation.test', 'staff');
        $this->make('Ben delos Reyes', 'rider@waterstation.test', 'rider');
        $this->make('Carla Cruz', 'customer@waterstation.test', 'customer');
    }

    private function make(string $name, string $email, string $role): void
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'phone' => '09171234567', 'password' => 'password', 'status' => 'active']
        );

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }
    }
}
