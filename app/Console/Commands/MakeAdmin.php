<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'app:make-admin {email : E-mailadres van de gebruiker} {--revoke : Beheerdersrechten intrekken}';

    protected $description = 'Geef of neem beheerdersrechten voor een gebruiker';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("Gebruiker met e-mail {$email} niet gevonden.");
            return self::FAILURE;
        }

        $user->is_admin = ! $this->option('revoke');
        $user->save();

        if ($this->option('revoke')) {
            $this->info("Beheerdersrechten ingetrokken voor {$user->name} ({$user->email}).");
        } else {
            $this->info("{$user->name} ({$user->email}) is nu beheerder.");
        }

        return self::SUCCESS;
    }
}
