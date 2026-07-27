<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('neighborhoods')) {
            return;
        }

        Schema::create('neighborhoods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('property_count')->default(0);
            $table->timestamps();

            $table->unique(['city_id', 'slug']);
            $table->index(['city_id', 'property_count']);
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neighborhoods');
    }
};
