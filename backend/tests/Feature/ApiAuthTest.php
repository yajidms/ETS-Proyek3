<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        $this->app['db']->setDefaultConnection('sqlite');

        $schema = Schema::connection('sqlite');
        $schema->dropIfExists('pengguna');
        $schema->create('pengguna', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_pengguna')->primary();
            $table->string('username', 255)->unique();
            $table->string('password');
            $table->string('email')->unique();
            $table->string('nama_depan');
            $table->string('nama_belakang');
            $table->string('role')->nullable();
        });
    }

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

    public function test_login_accepts_email_identifier(): void
    {
        config([
            'services.jwt.secret' => 'test-secret',
            'services.jwt.ttl' => 3600,
        ]);

        Pengguna::create([
            'id_pengguna' => 1000,
            'username' => 'citizen-01',
            'password' => Hash::make('public-password'),
            'email' => 'citizen@example.com',
            'nama_depan' => 'Citizen',
            'nama_belakang' => 'Tester',
            'role' => 'Public',
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'citizen@example.com',
            'password' => 'public-password',
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                'user' => [
                    'id' => 1000,
                    'username' => 'citizen-01',
                    'role' => 'Public',
                    'nama_depan' => 'Citizen',
                    'nama_belakang' => 'Tester',
                ],
            ]);
    }
}
