<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CIMA-044: wards — physical areas holding beds. `capacity` is the ward's
 * declared bed capacity (bed management in CIMA-045 must respect it);
 * deletions are blocked once the ward holds beds (unsafe-delete guard).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wards', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 24)->unique();
            $table->string('name', 120)->unique();
            $table->unsignedSmallInteger('capacity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wards');
    }
};
