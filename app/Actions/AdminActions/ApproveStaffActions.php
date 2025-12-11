<?php

namespace App\Actions\AdminActions;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class ApproveStaffAction
{
    public function handle(User $staff): User
    {
        if (!$staff->isStaff()) {
            throw new \Exception('User is not a staff member');
        }

        Log::info('Approving staff', ['staff_id' => $staff->id]);

        $staff->update(['is_approved' => true]);

        return $staff->fresh();
    }
}
