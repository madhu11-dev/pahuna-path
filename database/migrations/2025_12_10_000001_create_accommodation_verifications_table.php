<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accommodation_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accommodation_id')->constrained('accommodations')->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('users')->onDelete('cascade');
            $table->decimal('verification_fee', 10, 2)->default(10.00);
            $table->string('payment_method')->default('khalti'); // khalti or other payment methods
            $table->string('transaction_id')->nullable(); // Payment transaction ID
            $table->enum('payment_status', ['pending', 'completed', 'failed'])->default('completed');
            $table->timestamp('paid_at');
            $table->timestamps();

            // Ensure one verification payment per accommodation
            $table->unique('accommodation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accommodation_verifications');
    }
};
