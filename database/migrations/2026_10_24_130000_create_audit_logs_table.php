<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CIMA-042 / ADR-004: append-only audit trail. Rows are never updated or
 * deleted by app workflows, and `record_type`/`record_id` deliberately have
 * no FK or cascade: history must survive even if the underlying record is
 * gone. `actor_id` is nullable (system actions) and intentionally not
 * constrained with cascade — deleting a user never rewrites history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('actor_id')->nullable()->index();
            $table->string('action', 64);
            $table->string('record_type', 64);
            $table->ulid('record_id')->nullable()->index();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['record_type', 'record_id']);
        });

        // ADR-001/ADR-004: explicit FK with restrict — a deleted actor must
        // never cascade into the audit history (users are never hard-deleted).
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreign('actor_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
