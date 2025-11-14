<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
        'location',
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

    // Access latitude & longitude
    public function getLocationAttribute($value)
    {
        if (!$value) return null;

        $point = DB::selectOne(
            "SELECT ST_X(location::geometry) AS lng, ST_Y(location::geometry) AS lat FROM accommodations WHERE id = ?",
            [$this->id]
        );

        return [
            'latitude' => $point->lat,
            'longitude' => $point->lng,
        ];
    }

    // Set latitude & longitude
    public function setLocationAttribute($value)
    {
        if (is_array($value) && isset($value['latitude'], $value['longitude'])) {
            $this->attributes['location'] = DB::raw(
                "ST_SetSRID(ST_MakePoint({$value['longitude']}, {$value['latitude']}), 4326)"
            );
        } else {
            $this->attributes['location'] = null;
        }
    }
}
