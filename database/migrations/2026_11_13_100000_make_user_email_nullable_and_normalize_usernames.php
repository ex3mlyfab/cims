<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Usernames are the login identifier and are matched case-insensitively.
     * Normalise every stored username to lowercase (resolving any collisions
     * that only differed by case) and allow accounts without an email address.
     */
    public function up(): void
    {
        $rows = DB::table('users')
            ->orderBy('id')
            ->get(['id', 'username']);

        $taken = $rows
            ->map(static fn (object $user): string => Str::lower(trim((string) $user->username)))
            ->filter()
            ->values()
            ->all();

        foreach ($rows as $user) {
            $current = Str::lower(trim((string) $user->username));

            // Remove this row from the pool so an unchanged name is not
            // treated as a collision with itself.
            $index = array_search($current, $taken, true);

            if ($index !== false) {
                unset($taken[$index]);
            }

            $base = preg_replace('/[^a-z0-9._-]+/', '-', $current) ?? '';
            $base = trim($base, '.-_');
            $base = $base === '' ? 'user' : $base;

            $candidate = $base;
            $counter = 1;

            while (in_array($candidate, $taken, true)) {
                $counter++;
                $candidate = $base.'-'.$counter;
            }

            $taken[] = $candidate;

            if ($candidate !== $user->username) {
                DB::table('users')->where('id', $user->id)->update(['username' => $candidate]);
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    /**
     * Reverse the changes. Usernames cannot regain their original casing, so
     * only the email nullability (and the placeholder addresses needed to make
     * the column required again) is reverted.
     */
    public function down(): void
    {
        DB::table('users')
            ->whereNull('email')
            ->orderBy('id')
            ->each(function (object $user): void {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['email' => Str::lower((string) $user->id).'@example.invalid']);
            });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
