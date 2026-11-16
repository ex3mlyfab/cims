<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * An admission may be managed by more than one team, and the selection is
 * optional. The single `encounters.team_id` column is replaced by this
 * pivot; existing assignments are carried over before the column is
 * dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encounter_team', function (Blueprint $table) {
            $table->ulid('encounter_id');
            $table->ulid('team_id');

            $table->primary(['encounter_id', 'team_id']);

            $table->foreign('encounter_id')->references('id')->on('encounters')->cascadeOnDelete();
            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
        });

        DB::table('encounters')
            ->whereNotNull('team_id')
            ->select(['id', 'team_id'])
            ->orderBy('id')
            ->chunkById(200, function ($encounters): void {
                $rows = $encounters->map(static fn ($encounter): array => [
                    'encounter_id' => $encounter->id,
                    'team_id' => $encounter->team_id,
                ])->all();

                if ($rows !== []) {
                    DB::table('encounter_team')->insert($rows);
                }
            }, 'id');

        Schema::table('encounters', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropIndex(['team_id', 'arrived_at']);
        });

        Schema::table('encounters', function (Blueprint $table) {
            $table->dropColumn('team_id');
        });
    }

    public function down(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->foreignUlid('team_id')->nullable()->after('bed_id')->constrained('teams')->nullOnDelete();
            $table->index(['team_id', 'arrived_at']);
        });

        DB::table('encounters')->update([
            'team_id' => DB::table('encounter_team')
                ->select('team_id')
                ->whereColumn('encounter_team.encounter_id', 'encounters.id')
                ->limit(1),
        ]);

        Schema::dropIfExists('encounter_team');
    }
};
