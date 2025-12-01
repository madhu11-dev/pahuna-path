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
        'review',
        'user_id',
        'place_id',
        'latitude',
        'longitude',
        'is_verified',
        'average_rating',
        'review_count',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    // Relationship with Place
    public function place()
    {
        return $this->belongsTo(Place::class);
    }

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship with Reviews
    public function reviews()
    {
        return $this->hasMany(AccommodationReview::class);
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
