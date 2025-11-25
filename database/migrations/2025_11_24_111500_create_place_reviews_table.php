<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_reviews', function (Blueprint $table) {

            $table->id();
            
            $table->foreignId('place_id')->constrained('places')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            $table->float('rating', 2, 1);
            
            $table->timestamps();
            
            $table->index(['place_id', 'created_at'], 'idx_place_reviews_place_date');
            $table->index(['user_id', 'created_at'], 'idx_place_reviews_user_date');
            $table->index('rating', 'idx_place_reviews_rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_reviews');
    }
};