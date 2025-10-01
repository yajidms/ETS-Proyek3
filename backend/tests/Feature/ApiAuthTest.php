<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_and_roles(): void
    {
        $adminRole = Role::create(['name' => 'admin']);

        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ]);
        $user->assignRole($adminRole);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'expires_in',
                'roles',
            ])
            ->assertJsonFragment([
                'roles' => ['admin'],
            ]);
    }
}
