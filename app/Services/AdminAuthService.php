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



}