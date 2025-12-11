<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'accommodation_id',
        'room_name',
        'room_type',
        'has_ac',
        'capacity',
        'total_rooms',
        'base_price',
        'description',
        'images',
    ];

    protected $casts = [
        'images' => 'array',
        'has_ac' => 'boolean',
        'base_price' => 'decimal:2',
    ];

    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function getAvailableRooms($checkIn, $checkOut)
    {
        $bookedRooms = Booking::where('room_id', $this->id)
            ->where('booking_status', '!=', 'cancelled')
            ->where(function($query) use ($checkIn, $checkOut) {
                $query->whereBetween('check_in_date', [$checkIn, $checkOut])
                      ->orWhereBetween('check_out_date', [$checkIn, $checkOut])
                      ->orWhere(function($q) use ($checkIn, $checkOut) {
                          $q->where('check_in_date', '<=', $checkIn)
                            ->where('check_out_date', '>=', $checkOut);
                      });
            })
            ->sum('number_of_rooms');

        return $this->total_rooms - $bookedRooms;
    }
}
