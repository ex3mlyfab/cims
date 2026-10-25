<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CIMA-052: treatment_statuses — the configurable treatment workflow
 * statuses (ADR: status axes are distinct; this is the treatment-workflow
 * axis). Rows drive the admission create/update forms (CIMA-051/061) and
 * the public display status column (Phase 7). The `encounters` FKs that
 * reference this table arrive in CIMA-060; the in-use delete guard is
 * staged for then. Configurable so hospital sign-off on labels (R4) needs
 * no code change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_statuses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('slug', 30)->unique();
            $table->string('label', 60)->unique();
            $table->unsignedSmallInteger('sort_order')->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_statuses');
    }
};
