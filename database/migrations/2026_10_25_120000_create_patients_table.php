<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CIMA-050: patients — the master patient registry (ADR-001/003). The
 * human-facing `patient_code` is the public identifier the admission flow
 * searches on (public identifiers are never ULIDs). Encounter and bed
 * allocation tables arrive in CIMA-060; nothing here references them yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('patient_code', 30)->unique();
            $table->string('fullname', 120)->index();
            $table->string('sex', 20)->default('other');
            $table->unsignedSmallInteger('age');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
