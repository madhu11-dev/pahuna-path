<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('accommodation_id')->constrained()->onDelete('restrict');
            $table->foreignId('room_id')->constrained()->onDelete('restrict');
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->time('check_in_time')->default('12:00:00');
            $table->time('check_out_time')->default('12:00:00');
            $table->integer('number_of_rooms')->default(1);
            $table->integer('number_of_guests');
            $table->integer('total_nights');
            $table->decimal('room_subtotal', 10, 2);
            $table->decimal('services_subtotal', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->enum('booking_status', ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid', 'refunded'])->default('unpaid');
            $table->enum('payment_method', ['cash', 'khalti', 'card'])->default('cash');
            $table->string('khalti_transaction_id')->nullable();
            $table->timestamp('payment_verified_at')->nullable();
            $table->text('special_requests')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            
            $table->index('booking_reference');
            $table->index('user_id');
            $table->index('accommodation_id');
            $table->index('room_id');
            $table->index('booking_status');
            $table->index('payment_status');
            $table->index(['check_in_date', 'check_out_date']);
            $table->index(['accommodation_id', 'booking_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
