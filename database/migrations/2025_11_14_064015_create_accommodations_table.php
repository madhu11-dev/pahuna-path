<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accommodations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('images')->nullable();
            $table->string('type')->comment('hotel, restaurant, guesthouse, etc.');
            $table->text('google_map_link')->nullable();
            $table->text('description')->nullable();
            $table->decimal('review', 3, 2)->nullable();
            $table->double('latitude', 10, 6)->nullable();
            $table->double('longitude', 10, 6)->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('place_id')->constrained('places')->onDelete('cascade');
            $table->boolean('is_verified')->default(false)->comment('Admin verification for accommodations');
            $table->decimal('average_rating', 3, 2)->nullable()->comment('Average rating from reviews');
            $table->integer('review_count')->default(0)->comment('Total number of reviews');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accommodations');
    }
};
