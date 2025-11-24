<?php

namespace App\Actions\AuthorizationActions;

use Illuminate\Http\Request;

class LogoutUserAction
{
    /**
     * Logout the authenticated user by deleting their current access token
     *
     * @param Request $request
     * @return bool
     */
    public function handle(Request $request): bool
    {
        $user = $request->user();
        
        if ($user && $user->currentAccessToken()) {
            // Delete the current access token
            $user->currentAccessToken()->delete();
            return true;
        }
        
        return false;
    }
}