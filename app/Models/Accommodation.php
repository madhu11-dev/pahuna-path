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
}
