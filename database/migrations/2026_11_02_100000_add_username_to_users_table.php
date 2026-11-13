<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Add the unique `username` used for login. Existing accounts are
     * backfilled from the local part of their email (sanitized, with a
     * numeric suffix when it would collide) so the column can be non-null
     * and unique right away.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->after('name');
        });

        // Backfill from the email local part, keeping names unique and
        // login-friendly ([a-z0-9._-] only).
        $taken = [];
        $forUpdate = [];

        DB::table('users')
            ->orderBy('id')
            ->get(['id', 'email', 'username'])
            ->each(function (object $user) use (&$taken, &$forUpdate): void {
                if (isset($user->username) && $user->username !== null) {
                    $taken[] = Str::lower($user->username);

                    return;
                }

                $base = Str::lower((string) strtok((string) $user->email, '@'));
                $base = preg_replace('/[^a-z0-9._-]+/', '-', $base);
                $base = trim($base !== '' && $base !== false ? $base : 'user', '.');
                $base = $base === '' ? 'user' : $base;

                $candidate = $base;
                $counter = 1;

                while (in_array($candidate, $taken, true)) {
                    $counter++;
                    $candidate = $base.'-'.$counter;
                }

                $taken[] = $candidate;
                $forUpdate[$user->id] = $candidate;
            });

        foreach ($forUpdate as $id => $username) {
            DB::table('users')->where('id', $id)->update(['username' => $username]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
