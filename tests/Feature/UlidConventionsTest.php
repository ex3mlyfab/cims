<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/*
 * CIMA-013: ULID key and foreign-key compatibility tests.
 * Verifies CIMA-010 conventions before domain tables are introduced.
 */
class UlidConventionsTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create();
    }

    public function test_users_table_uses_ulid_primary_keys_generated_by_model(): void
    {
        $user = $this->user();

        $this->assertTrue(Str::isUlid($user->id));
        $this->assertSame('varchar', DB::getSchemaBuilder()->getColumnType('users', 'id'));
    }

    public function test_ulid_user_id_is_route_key(): void
    {
        $user = $this->user();

        $this->assertSame($user->id, $user->getRouteKey());
    }

    public function test_sessions_reference_users_with_a_compatible_ulid_column(): void
    {
        $user = $this->user();

        DB::table('sessions')->insert([
            'id' => 'test-session-id',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => 'base64',
            'last_activity' => time(),
        ]);

        $this->assertTrue((bool) DB::table('sessions')->where('user_id', $user->id)->exists());
    }

    public function test_passkeys_reference_users_with_a_compatible_ulid_column(): void
    {
        $user = $this->user();

        DB::table('passkeys')->insert([
            'id' => (string) Str::ulid(),
            'user_id' => $user->id,
            'name' => 'test key',
            'credential_id' => Str::random(32),
            'credential' => '{}',
            'last_used_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertTrue((bool) DB::table('passkeys')->where('user_id', $user->id)->exists());
    }
}
