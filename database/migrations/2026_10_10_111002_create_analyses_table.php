<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analyses', function (Blueprint $table): void {
            $table->string('id', 8)->primary();
            $table->string('status', 20)->default('pending');
            $table->string('restaurant_name', 120);
            $table->string('city', 120);
            $table->string('restaurant_type', 80);
            $table->string('goal', 500)->nullable();
            $table->unsignedSmallInteger('image_count')->default(0);
            $table->string('model', 80)->default('');
            $table->boolean('mock')->default(false);
            $table->text('error')->nullable();
            $table->json('menu')->nullable();
            $table->json('omnes')->nullable();
            $table->json('optimization')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyses');
    }
};
