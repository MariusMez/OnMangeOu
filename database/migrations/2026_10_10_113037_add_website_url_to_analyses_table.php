<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analyses', function (Blueprint $table): void {
            $table->string('website_url', 500)->nullable()->after('goal');
        });
    }

    public function down(): void
    {
        Schema::table('analyses', function (Blueprint $table): void {
            $table->dropColumn('website_url');
        });
    }
};
