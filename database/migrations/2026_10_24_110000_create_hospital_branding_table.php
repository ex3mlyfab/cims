<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CIMA-040: hospital branding settings (ADR-005).
 *
 * Singleton settings row: name, logo, display text and the theme engine
 * hospital-level defaults. It must never silently overwrite a user's own
 * appearance choice — user preferences win (see HandleAppearance).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hospital_branding', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('logo_path')->nullable();
            $table->string('display_text', 120)->nullable();
            $table->string('default_preset', 64)->default('clinical-indigo');
            $table->json('allowed_presets')->nullable(); // null = all presets allowed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_branding');
    }
};
