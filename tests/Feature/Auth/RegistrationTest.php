<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public self-registration is intentionally disabled: this is an internal
 * admin system, and any account that self-registers with no role either gets
 * stranded on a 403 or, worse, is silently promoted to admin the next time
 * RoleSeeder runs. Accounts are provisioned by an admin (teachers, via
 * Master Data) or via seeding — never through a public form.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_routes_are_not_reachable(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }
}
