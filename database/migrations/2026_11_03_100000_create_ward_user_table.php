<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ward assignment pivot: which wards a staff account may see and admit
 * into. Everyone except `super admin` and `hospital administrator` is
 * scoped to the rows in this table (no row = no ward access).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ward_user', function (Blueprint $table) {
            $table->ulid('ward_id');
            $table->ulid('user_id');

            $table->primary(['ward_id', 'user_id']);

            $table->foreign('ward_id')->references('id')->on('wards')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ward_user');
    }
};
