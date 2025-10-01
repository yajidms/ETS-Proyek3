<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_and_roles(): void
    {
        config([
            'services.jwt.secret' => 'test-secret',
            'services.jwt.ttl' => 3600,
        ]);

        Pengguna::create([
            'id_pengguna' => 999,
            'username' => 'admin-test',
            'password' => Hash::make('password-secret'),
            'email' => 'admin@example.com',
            'nama_depan' => 'Admin',
            'nama_belakang' => 'Tester',
            'role' => 'Admin',
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'admin-test',
            'password' => 'password-secret',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'token_type',
                'expires_in',
                'user' => [
                    'id',
                    'username',
                    'role',
                    'nama_depan',
                    'nama_belakang',
                ],
            ])
            ->assertJsonFragment([
                'user' => [
                    'id' => 999,
                    'username' => 'admin-test',
                    'role' => 'Admin',
                    'nama_depan' => 'Admin',
                    'nama_belakang' => 'Tester',
                ],
            ]);
    }
}
