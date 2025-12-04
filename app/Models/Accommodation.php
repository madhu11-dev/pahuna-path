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
