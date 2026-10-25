<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CIMA-046: bed_events — append-only bed occupation/operational history
 * (ADR-003 + cima-database #6). Every bed lifecycle fact lands here:
 * creation, manual state moves (and, from CIMA-060 on, allocation events).
 * The bed FK and the actor FK are restrict-on-delete: a bed that has
 * history is never hard-deleted, and actor references survive.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bed_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('bed_id')->constrained('beds')->restrictOnDelete();
            $table->foreignUlid('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('action', 60);
            $table->json('details')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['bed_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bed_events');
    }
};
