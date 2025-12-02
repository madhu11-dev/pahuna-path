<?php

namespace App\Services;

use App\Actions\AuthorizationActions\RegisterUserAction;
use App\Models\User;
use App\Models\Accommodation;
use Illuminate\Support\Facades\DB;

class StaffService
{
    public function __construct(
        protected RegisterUserAction $registerUserAction,
        protected EmailService $emailService
    ) {}

    public function registerStaff(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Set staff-specific data
            $data['utype'] = 'STF';

            // Register the user - they can access after email verification
            $user = $this->registerUserAction->handle($data);

            return $user;
        });
    }

    public function getDashboardData(User $staff): array
    {
        // Staff can see basic statistics but need approval to manage accommodations
        $staffAccommodations = Accommodation::where('user_id', $staff->id)->get();
        
        $totalReviews = $staffAccommodations->sum('review_count');
        $avgRating = $staffAccommodations->where('average_rating', '>', 0)->avg('average_rating');
        
        return [
            'staff' => [
                'name' => $staff->name,
                'email' => $staff->email,
                'phone' => $staff->phone,
                'hotel_name' => $staff->hotel_name,
                'profile_picture' => $staff->profile_picture,
                'email_verified_at' => $staff->email_verified_at,
            ],
            'hotelStats' => [
                'totalAccommodations' => $staffAccommodations->count(),
                'verifiedAccommodations' => $staffAccommodations->where('is_verified', true)->count(),
                'pendingAccommodations' => $staffAccommodations->where('is_verified', false)->count(),
                'totalReviews' => $totalReviews,
                'averageRating' => $avgRating ? round($avgRating, 2) : 0,
            ]
        ];
    }

    public function updateStaffProfile(User $staff, array $data): User
    {
        if (isset($data['profile_picture'])) {
            // Handle file upload
            $file = $data['profile_picture'];
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/profile_pictures', $filename);
            $data['profile_picture'] = 'profile_pictures/' . $filename;
        }

        $staff->update($data);
        return $staff->fresh();
    }
}