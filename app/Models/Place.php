<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    use HasFactory;

    protected $fillable = [
        'place_name',
        'description',
        'images',
        'google_map_link',
        'user_id',
        'latitude',
        'longitude',
        'is_merged',
        'merged_from_ids',
    ];

    protected $casts = [
        'images' => 'array',
        'is_merged' => 'boolean',
        'merged_from_ids' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviews()
    {
        return $this->hasMany(PlaceReview::class);
    }

    // Get average rating from reviews
    public function getAverageRatingAttribute()
    {
        try {
            return $this->reviews()->avg('rating') ?? 0;
        } catch (\Exception $e) {
            // Return 0 if there's an error (e.g., table doesn't exist yet)
            return 0;
        }
    }

    // Get total review count
    public function getReviewCountAttribute()
    {
        try {
            return $this->reviews()->count();
        } catch (\Exception $e) {
            // Return 0 if there's an error (e.g., table doesn't exist yet)
            return 0;
        }
    }

    // Scope for non-merged places (for admin use)
    public function scopeNotMerged($query)
    {
        return $query->where('is_merged', false);
    }

    // Scope for merged places (for admin use)
    public function scopeMerged($query)
    {
        return $query->where('is_merged', true);
    }
}
