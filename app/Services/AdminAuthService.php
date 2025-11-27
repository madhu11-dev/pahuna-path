<?php

namespace App\Services;

use App\Models\User;
use App\Models\Place;
use App\Models\PlaceReview;
use App\Models\Accommodation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminAuthService
{
    public function adminLogout($user): bool
    {
        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
            return true;
        }
        
        return false;
    }
      public function getDashboardStats(): array
    {
        $totalUsers = User::count();
        $totalPlaces = Place::count();
        $totalHotels = Accommodation::count();
        $totalReviews = PlaceReview::count();
        
        // Get monthly visitor data for graph (using place reviews as proxy for visits)
        $monthlyVisits = PlaceReview::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as visits')
        )
        ->whereYear('created_at', date('Y'))
        ->groupBy('month')
        ->orderBy('month')
        ->get()
        ->mapWithKeys(function ($item) {
            return [$item->month => $item->visits];
        });


       // Fill missing months with 0
        $visitorGraphData = [];
        for ($i = 1; $i <= 12; $i++) {
            $visitorGraphData[] = [
                'month' => date('M', mktime(0, 0, 0, $i, 1)),
                'visits' => $monthlyVisits->get($i, 0)
            ];
        }

        return [
            'stats' => [
                'total_users' => $totalUsers,
                'total_visitors' => $totalReviews, // Using reviews as proxy for visitors
                'total_places' => $totalPlaces,
                'total_hotels' => $totalHotels,
                'total_reviews' => $totalReviews
            ],
            'visitor_graph_data' => $visitorGraphData
        ];
    }

}