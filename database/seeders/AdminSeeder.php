<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $name = env('ADMIN_NAME', 'Beheerder');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command?->error('ADMIN_EMAIL en ADMIN_PASSWORD moeten ingesteld zijn als env-variabelen.');
            return;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->is_admin = true;
            if (filled($password)) {
                $user->password = Hash::make($password);
            }
            $user->save();
            $this->command?->info("Bestaande gebruiker {$user->email} is nu beheerder en wachtwoord is bijgewerkt.");
            return;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->command?->info("Beheerder {$user->email} aangemaakt.");
    }
}
