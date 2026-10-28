<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['model_has_permissions', 'model_has_roles'] as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'model_ulid')) {
                continue;
            }

            Schema::table($table, static function (Blueprint $table): void {
                $table->renameColumn('model_id', 'model_ulid');
            });
        }
    }

    public function down(): void
    {
        foreach (['model_has_permissions', 'model_has_roles'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'model_ulid')) {
                continue;
            }

            Schema::table($table, static function (Blueprint $table): void {
                $table->renameColumn('model_ulid', 'model_id');
            });
        }
    }
};
