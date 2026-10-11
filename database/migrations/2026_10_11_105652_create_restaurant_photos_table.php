<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->char('thumbnail_hash', 40);
            $table->string('kind', 20);
            $table->boolean('has_prices')->default(false);
            $table->string('attribution', 255)->nullable();
            $table->string('path', 255)->nullable();
            $table->timestamps();

            $table->unique(['restaurant_id', 'thumbnail_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_photos');
    }
};
