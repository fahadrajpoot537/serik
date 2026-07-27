<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('nearby_cities')) {
            return;
        }

        Schema::create('nearby_cities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->foreignId('nearby_city_id')->constrained('cities')->cascadeOnDelete();
            $table->decimal('distance_km', 8, 2)->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['city_id', 'nearby_city_id']);
            $table->index(['city_id', 'distance_km']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nearby_cities');
    }
};
