<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CIMA-051: admission record create flow. Encounters are the patient-level
     * admission ledger that sits atop a single active bed allocation (CIMA-060).
     */
    public function up(): void
    {
        Schema::create('encounters', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignUlid('ward_id')->constrained('wards')->restrictOnDelete();
            $table->foreignUlid('bed_id')->constrained('beds')->restrictOnDelete();
            $table->foreignUlid('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignUlid('treatment_status_id')->nullable()->constrained('treatment_statuses')->nullOnDelete();
            $table->timestamp('arrived_at')->useCurrent();
            $table->timestamp('discharged_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'discharged_at']);
            $table->index(['ward_id', 'arrived_at']);
            $table->index(['bed_id', 'discharged_at']);
            $table->index(['team_id', 'arrived_at']);
            $table->index(['treatment_status_id', 'arrived_at']);
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX encounters_active_patient_unique ON encounters (patient_id) WHERE discharged_at IS NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('encounters');
    }
};
