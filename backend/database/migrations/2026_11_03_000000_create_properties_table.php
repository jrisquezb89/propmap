<?php

use App\Enums\PropertyType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            // Whole dollars: listing prices have no cents, and integers are exact (never float for money).
            $table->unsignedInteger('price');
            $table->unsignedTinyInteger('bedrooms');
            // Half baths exist (2.5), so one decimal place.
            $table->decimal('bathrooms', 3, 1);
            $table->unsignedInteger('sqft');
            // String + CHECK constraint instead of a native Postgres enum, which is awkward to alter later.
            $table->string('type', 20);
            $table->string('address');
            $table->string('neighborhood', 60);
            // 7 decimal places is ~1cm precision.
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('image_url', 500);
            $table->timestampTz('listed_at');
            $table->timestampsTz();

            $table->index(['lat', 'lng']);
            $table->index('price');
            $table->index('listed_at'); // default sort: newest first
        });

        $types = implode("', '", PropertyType::values());
        DB::statement("ALTER TABLE properties ADD CONSTRAINT properties_type_check CHECK (type IN ('{$types}'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
