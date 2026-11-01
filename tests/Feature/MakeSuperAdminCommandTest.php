<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MakeSuperAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_promote_existing_user_to_super_admin(): void
    {
        $user = User::factory()->create([
            'email' => 'regular@example.com',
            'is_super_admin' => false,
        ]);

        $this->artisan('make:super-admin', ['email' => 'regular@example.com'])
            ->expectsOutputToContain('promoted to Platform Super Admin')
            ->assertExitCode(0);

        $user->refresh();
        $this->assertTrue($user->is_super_admin);
    }

    public function test_can_create_new_super_admin_user_via_command(): void
    {
        $this->artisan('make:super-admin', [
            'email' => 'master@example.com',
            '--name' => 'Master Super Admin',
            '--password' => 'SecurePass123!',
        ])
            ->expectsOutputToContain('created successfully')
            ->assertExitCode(0);

        $user = User::where('email', 'master@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Master Super Admin', $user->name);
        $this->assertTrue($user->is_super_admin);
        $this->assertTrue(Hash::check('SecurePass123!', $user->password));
    }

    public function test_fails_with_invalid_email(): void
    {
        $this->artisan('make:super-admin', ['email' => 'not-an-email'])
            ->expectsOutputToContain('Invalid email address')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'not-an-email']);
    }
}

