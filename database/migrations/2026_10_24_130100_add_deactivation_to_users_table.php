<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CIMA-042: account deactivation (security rule 10 — deactivated users are
 * blocked at middleware and their sessions invalidated; audit history and
 * the account itself are preserved, so users are never hard-deleted).
 * Password becomes nullable: admin-created users receive a password-set
 * link instead of an admin-chosen password.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('deactivated_at')->nullable();
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('deactivated_at')->nullable()->change();
            $table->string('password')->nullable(false)->change();
        });
    }
};
