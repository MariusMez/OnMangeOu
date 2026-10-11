<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table): void {
            $table->id();
            $table->string('google_place_id', 255)->nullable()->unique();
            $table->string('name', 160);
            $table->string('address', 255)->nullable();
            $table->string('postal_code', 5)->nullable()->index();
            $table->string('city', 120)->nullable();
            $table->string('departement_code', 3)->nullable()->index();
            $table->string('region_code', 2)->nullable()->index();
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->string('restaurant_type', 80)->index();
            $table->string('google_primary_type', 80)->nullable();
            $table->json('google_types')->nullable();
            $table->unsignedTinyInteger('price_level')->nullable();
            $table->decimal('rating', 2, 1)->nullable();
            $table->unsignedInteger('rating_count')->nullable();
            $table->string('website_url', 500)->nullable();
            $table->string('business_status', 30)->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
