<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dishes', function (Blueprint $table): void {
            $table->string('analysis_id', 8)->nullable()->change();
            $table->foreignId('restaurant_id')->nullable()->after('analysis_id')->constrained()->cascadeOnDelete();
            $table->string('source', 20)->default('analysis')->after('restaurant_id');
            $table->string('postal_code', 5)->nullable()->after('city');
            $table->string('departement_code', 3)->nullable()->after('postal_code');
            $table->string('region_code', 2)->nullable()->after('departement_code');

            $table->index(['departement_code', 'category', 'year']);
            $table->index(['region_code', 'category', 'year']);
            $table->index(['postal_code', 'category', 'year']);
        });
    }

    public function down(): void
    {
        DB::table('dishes')->whereNull('analysis_id')->delete();

        Schema::table('dishes', function (Blueprint $table): void {
            $table->dropIndex(['departement_code', 'category', 'year']);
            $table->dropIndex(['region_code', 'category', 'year']);
            $table->dropIndex(['postal_code', 'category', 'year']);
            $table->dropConstrainedForeignId('restaurant_id');
            $table->dropColumn(['source', 'postal_code', 'departement_code', 'region_code']);
            $table->string('analysis_id', 8)->nullable(false)->change();
        });
    }
};
