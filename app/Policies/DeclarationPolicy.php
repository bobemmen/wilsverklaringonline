<?php

namespace App\Policies;

use App\Models\Declaration;
use App\Models\User;

class DeclarationPolicy
{
    public function view(User $user, Declaration $declaration): bool
    {
        return $user->id === $declaration->user_id;
    }

    public function update(User $user, Declaration $declaration): bool
    {
        return $user->id === $declaration->user_id;
    }

    public function delete(User $user, Declaration $declaration): bool
    {
        return $user->id === $declaration->user_id;
    }
}
