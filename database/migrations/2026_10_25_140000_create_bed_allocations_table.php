<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CIMA-060: bed allocation ledger. One row per occupancy period, with
     * a nullable ended_at so active rows are obvious while historical rows
     * remain queryable. The service layer enforces the active-allocation
     * guard with a transaction+lock, while the SQLite migration adds a
     * partial unique index to prevent accidental double-booking in tests.
     */
    public function up(): void
    {
        Schema::create('bed_allocations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('bed_id')->constrained('beds')->restrictOnDelete();
            $table->foreignUlid('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignUlid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->string('reason', 40)->default('admission');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('bed_id');
            $table->index('patient_id');
            $table->index('ended_at');
            $table->index(['bed_id', 'started_at']);
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX bed_allocations_active_unique ON bed_allocations (bed_id) WHERE ended_at IS NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bed_allocations');
    }
};
