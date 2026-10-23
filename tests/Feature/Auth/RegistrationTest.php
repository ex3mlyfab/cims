<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/*
 * CIMA-020: Registration is now disabled — all users must be created by
 * authorized administrators (docs/adr/ADR-002-authorization-super-admin.md).
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_route_no_longer_exists(): void
    {
        $this->assertFalse(Route::has('register'));
        $this->assertFalse(Route::has('register.store'));
    }

    public function test_registration_screen_is_not_rendered(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_new_users_cannot_self_register(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();
    }
}
