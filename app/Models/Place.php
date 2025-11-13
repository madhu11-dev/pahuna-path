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
    ];

    protected $casts = [
        'images' => 'array', // JSON array for images
    ];

    /**
     * Relationship to User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Set the PostGIS location from latitude and longitude
     */
    public function setLocation(float $latitude, float $longitude)
    {
        DB::update("UPDATE places SET location = ST_GeogFromText('POINT($longitude $latitude)') WHERE id = ?", [$this->id]);
    }

    /**
     * Get latitude
     */
    public function getLatitudeAttribute()
    {
        $point = DB::selectOne("SELECT ST_Y(location::geometry) AS lat FROM places WHERE id = ?", [$this->id]);
        return $point?->lat;
    }

    /**
     * Get longitude
     */
    public function getLongitudeAttribute()
    {
        $point = DB::selectOne("SELECT ST_X(location::geometry) AS lng FROM places WHERE id = ?", [$this->id]);
        return $point?->lng;
    }
}
