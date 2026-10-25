<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CIMA-045: beds — physical bed units inside a ward. `number` is the bed's
 * identifier within its ward (unique per ward, not globally). `status`
 * follows the bed state machine (docs/IMPLEMENTATION_PLAN.md §3):
 * available → occupied → cleaning → available | out_of_service. The
 * occupied state is never set directly from the management UI — it is
 * entered through the allocation workflow (CIMA-060), which also guarantees
 * that an occupied bed always has an active `bed_allocations` row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beds', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('ward_id')->constrained('wards')->restrictOnDelete();
            $table->string('number', 24);
            $table->string('status', 20)->default('available');
            $table->timestamps();

            $table->unique(['ward_id', 'number']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beds');
    }
};
