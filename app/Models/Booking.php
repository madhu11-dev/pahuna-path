<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'booking_reference',
        'user_id',
        'accommodation_id',
        'room_id',
        'check_in_date',
        'check_out_date',
        'check_in_time',
        'check_out_time',
        'number_of_rooms',
        'number_of_guests',
        'total_nights',
        'room_subtotal',
        'services_subtotal',
        'total_amount',
        'booking_status',
        'payment_status',
        'payment_method',
        'khalti_transaction_id',
        'payment_verified_at',
        'special_requests',
        'cancellation_reason',
        'cancelled_at',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'room_subtotal' => 'decimal:2',
        'services_subtotal' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'payment_verified_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($booking) {
            if (!$booking->booking_reference) {
                $count = static::whereDate('created_at', today())->count() + 1;
                $booking->booking_reference = 'BOOK' . date('Ymd') . str_pad($count, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(BookingService::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // Payment Methods
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->booking_status, ['pending', 'confirmed']) && 
               $this->check_in_date > now();
    }

    public function getRefundAmount(): float
    {
        if (!$this->isPaid()) {
            return 0;
        }

        $daysUntilCheckIn = now()->diffInDays($this->check_in_date, false);
        
        // 80% refund if cancelled 2 or more days before check-in
        if ($daysUntilCheckIn >= 2) {
            return round($this->total_amount * 0.80, 2);
        }

        // No refund if less than 2 days
        return 0;
    }
}
