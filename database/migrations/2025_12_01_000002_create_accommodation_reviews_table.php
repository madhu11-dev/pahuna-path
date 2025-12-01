<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accommodation_reviews', function (Blueprint $table) {
            // placeholder for ccomodation reviews, will be subject to changed
            $table->id();
            $table->foreignId('accommodation_id')->constrained('accommodations')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('rating')->comment('Rating from 1-5');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['accommodation_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accommodation_reviews');
    }
};