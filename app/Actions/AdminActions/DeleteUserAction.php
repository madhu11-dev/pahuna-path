<?php

namespace App\Actions\AdminActions;

use App\Models\User;

class DeleteUserAction
{
    public function handle(User $user): void
    {
        // Delete user's tokens
        $user->tokens()->delete();
        
        // Delete user
        $user->delete();
    }
}
