<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccommodationVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'accommodation_id',
        'staff_id',
        'verification_fee',
        'payment_method',
        'transaction_id',
        'payment_status',
        'paid_at',
    ];

    protected $casts = [
        'verification_fee' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function accommodation()
    {
        return $this->belongsTo(Accommodation::class);
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
