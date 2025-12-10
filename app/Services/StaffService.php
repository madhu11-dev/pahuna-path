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
            $data['utype'] = 'STF';
            $user = $this->registerUserAction->handle($data);

            return $user;
        });
    }

    public function getDashboardData(User $staff): array
    {
        $accommodations = $staff->accommodations;

        $totalReviews = 0;
        $avgRating = 0;

        if ($accommodations->isNotEmpty()) {
            $accommodationIds = $accommodations->pluck('id');
            $reviewStats = DB::table('accommodation_reviews')
                ->whereIn('accommodation_id', $accommodationIds)
                ->selectRaw('COUNT(*) as total_reviews, AVG(rating) as avg_rating')
                ->first();

            $totalReviews = $reviewStats ? (int) $reviewStats->total_reviews : 0;
            $avgRating = $reviewStats && $reviewStats->avg_rating ? round((float) $reviewStats->avg_rating, 1) : 0;
        }

        return [
            'staff' => [
                'name' => $staff->name,
                'email' => $staff->email,
                'phone' => $staff->phone,
                'profile_picture' => $staff->profile_picture,
                'email_verified_at' => $staff->email_verified_at,
            ],
            'accommodations' => $accommodations->map(function ($accommodation) {
                $reviewStats = \App\Models\AccommodationReview::where('accommodation_id', $accommodation->id)
                    ->selectRaw('AVG(rating) as average_rating, COUNT(*) as review_count')
                    ->first();

                return [
                    'id' => $accommodation->id,
                    'name' => $accommodation->name,
                    'type' => $accommodation->type,
                    'description' => $accommodation->description,
                    'images' => $accommodation->images,
                    'google_map_link' => $accommodation->google_map_link,
                    'latitude' => $accommodation->latitude,
                    'longitude' => $accommodation->longitude,
                    'is_verified' => $accommodation->is_verified,
                    'has_paid_verification' => $accommodation->hasVerificationPayment(),
                    'average_rating' => $reviewStats && $reviewStats->average_rating ? round((float) $reviewStats->average_rating, 1) : 0,
                    'review_count' => $reviewStats ? (int) $reviewStats->review_count : 0,
                ];
            }),
            'stats' => [
                'totalAccommodations' => $accommodations->count(),
                'totalReviews' => $totalReviews,
                'averageRating' => $avgRating,
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
