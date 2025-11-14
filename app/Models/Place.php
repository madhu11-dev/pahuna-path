<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Place extends Model
{
    use HasFactory;

    protected $fillable = [
        'place_name',
        'images',
        'google_map_link',
        'caption',
        'review',
        'user_id',
        'location'
    ];

    protected $casts = [
        'images' => 'array', // JSON array for images
    ];

    // Relationship to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mutator: store lat/lng as geography(POINT, 4326)
     */
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

    /**
     * Accessor: return latitude/longitude array
     */
    public function getLocationAttribute($value)
    {
        if (!$this->exists) return null;

        $point = DB::selectOne(
            "SELECT ST_X(location::geometry) AS lng, ST_Y(location::geometry) AS lat FROM places WHERE id = ?",
            [$this->id]
        );

        return $point ? ['latitude' => $point->lat, 'longitude' => $point->lng] : null;
    }
}
