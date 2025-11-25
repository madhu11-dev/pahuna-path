<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaceReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'place_id',
        'user_id',
        'rating',
        'comment',
    ];

    protected $casts = [
        'rating' => 'float',
    ];

    public function place()
    {
        return $this->belongsTo(Place::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}