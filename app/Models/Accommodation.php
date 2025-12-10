<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Accommodation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'images',
        'type',
        'google_map_link',
        'description',
        'staff_id',
        'latitude',
        'longitude',
        'checkout_policy',
        'cancellation_policy',
        'is_verified',
        'average_rating',
        'review_count',
    ];

    protected $casts = [
        'images' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'average_rating' => 'float',
        'is_verified' => 'boolean',
    ];

    // Relationship with Staff User
    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }


    // Relationship with Reviews
    public function reviews()
    {
        return $this->hasMany(AccommodationReview::class);
    }

    // Relationship with Rooms
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    // Relationship with Extra Services
    public function extraServices()
    {
        return $this->hasMany(ExtraService::class);
    }

    // Relationship with Bookings
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    // Relationship with Verification Payment
    public function verification()
    {
        return $this->hasOne(AccommodationVerification::class);
    }

    // Check if accommodation has paid verification fee
    public function hasVerificationPayment()
    {
        return $this->verification()->where('payment_status', 'completed')->exists();
    }

    // Update average rating when reviews change
    public function updateAverageRating()
    {
        $avgRating = $this->reviews()->avg('rating');
        $reviewCount = $this->reviews()->count();

        $this->update([
            'average_rating' => $avgRating ? round($avgRating, 2) : null,
            'review_count' => $reviewCount,
        ]);
    }
}
