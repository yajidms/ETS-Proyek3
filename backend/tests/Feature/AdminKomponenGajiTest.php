<?php

namespace Tests\Feature;

use App\Http\Middleware\JwtMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Models\KomponenGaji;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminKomponenGajiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        $this->app['db']->setDefaultConnection('sqlite');

        $schema = Schema::connection('sqlite');
        $schema->dropIfExists('penggajian');
        $schema->dropIfExists('komponen_gaji');

        $schema->create('komponen_gaji', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_komponen_gaji')->primary();
            $table->string('nama_komponen', 100);
            $table->string('kategori');
            $table->string('jabatan');
            $table->decimal('nominal', 17, 2);
            $table->string('satuan');
        });

        $schema->create('penggajian', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_komponen_gaji');
            $table->unsignedBigInteger('id_anggota');
        });
    }

    public function test_store_creates_new_component(): void
    {
        $this->withoutMiddleware([JwtMiddleware::class, RoleMiddleware::class]);

        $payload = [
            'id_komponen_gaji' => 301,
            'nama_komponen' => 'Tunjangan Baru',
            'kategori' => 'Tunjangan Lain',
            'jabatan' => 'Semua',
            'nominal' => 1500000,
            'satuan' => 'Bulan',
        ];

        $response = $this->postJson('/api/admin/komponen-gaji', $payload);

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'id_komponen_gaji' => 301,
                'nama_komponen' => 'Tunjangan Baru',
                'kategori' => 'Tunjangan Lain',
                'jabatan' => 'Semua',
                'nominal' => 1500000,
                'satuan' => 'Bulan',
            ]);

        $this->assertDatabaseHas('komponen_gaji', [
            'id_komponen_gaji' => 301,
            'nama_komponen' => 'Tunjangan Baru',
        ]);
    }

    public function test_index_supports_search_and_filters(): void
    {
        $this->withoutMiddleware([JwtMiddleware::class, RoleMiddleware::class]);

        KomponenGaji::create([
            'id_komponen_gaji' => 101,
            'nama_komponen' => 'Gaji Pokok Ketua',
            'kategori' => 'Gaji Pokok',
            'jabatan' => 'Ketua',
            'nominal' => 5040000,
            'satuan' => 'Bulan',
        ]);

        KomponenGaji::create([
            'id_komponen_gaji' => 102,
            'nama_komponen' => 'Tunjangan Beras',
            'kategori' => 'Tunjangan Melekat',
            'jabatan' => 'Semua',
            'nominal' => 12000000,
            'satuan' => 'Bulan',
        ]);

        $response = $this->getJson('/api/admin/komponen-gaji?search=beras&kategori=Tunjangan%20Melekat');

        $response
            ->assertOk()
            ->assertJsonFragment(['id_komponen_gaji' => 102])
            ->assertJsonMissing(['id_komponen_gaji' => 101]);
    }

    public function test_update_modifies_existing_component(): void
    {
        $this->withoutMiddleware([JwtMiddleware::class, RoleMiddleware::class]);

        KomponenGaji::create([
            'id_komponen_gaji' => 401,
            'nama_komponen' => 'Tunjangan Komunikasi',
            'kategori' => 'Tunjangan Lain',
            'jabatan' => 'Semua',
            'nominal' => 15554000,
            'satuan' => 'Bulan',
        ]);

        $response = $this->putJson('/api/admin/komponen-gaji/401', [
            'nama_komponen' => 'Tunjangan Komunikasi Baru',
            'nominal' => 16000000,
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id_komponen_gaji' => 401,
                'nama_komponen' => 'Tunjangan Komunikasi Baru',
                'nominal' => 16000000,
            ]);

        $this->assertDatabaseHas('komponen_gaji', [
            'id_komponen_gaji' => 401,
            'nominal' => 16000000,
        ]);
    }

    public function test_destroy_removes_component_and_relations(): void
    {
        $this->withoutMiddleware([JwtMiddleware::class, RoleMiddleware::class]);

        KomponenGaji::create([
            'id_komponen_gaji' => 501,
            'nama_komponen' => 'Tunjangan Test',
            'kategori' => 'Tunjangan Lain',
            'jabatan' => 'Semua',
            'nominal' => 1000000,
            'satuan' => 'Bulan',
        ]);

        DB::table('penggajian')->insert([
            'id_komponen_gaji' => 501,
            'id_anggota' => 999,
        ]);

        $response = $this->deleteJson('/api/admin/komponen-gaji/501');

        $response->assertNoContent();

        $this->assertDatabaseMissing('komponen_gaji', ['id_komponen_gaji' => 501]);
        $this->assertDatabaseMissing('penggajian', ['id_komponen_gaji' => 501]);
    }
}
