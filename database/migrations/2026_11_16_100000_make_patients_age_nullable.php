<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Age is optional in the patient registry: staff can register a patient
 * without knowing their age, and complete it later. The admission flow's
 * inline "Register patient" modal no longer requires it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->unsignedSmallInteger('age')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('patients')->whereNull('age')->update(['age' => 0]);

        Schema::table('patients', function (Blueprint $table) {
            $table->unsignedSmallInteger('age')->nullable(false)->change();
        });
    }
};
