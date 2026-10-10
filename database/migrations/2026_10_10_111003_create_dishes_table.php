<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dishes', function (Blueprint $table): void {
            $table->id();
            $table->string('analysis_id', 8);
            $table->string('restaurant_name', 120);
            $table->string('city', 120);
            $table->string('restaurant_type', 80);
            $table->unsignedSmallInteger('year');
            $table->string('section', 120);
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('category', 20);
            $table->decimal('price', 8, 2)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->boolean('is_highlighted')->default(false);

            $table->foreign('analysis_id')->references('id')->on('analyses')->cascadeOnDelete();
            $table->index(['category', 'city', 'restaurant_type', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dishes');
    }
};
