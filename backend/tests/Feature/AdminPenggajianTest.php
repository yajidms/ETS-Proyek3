<?php

namespace Tests\Feature;

use App\Http\Middleware\JwtMiddleware;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminPenggajianTest extends TestCase
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
        $schema->dropIfExists('anggota');

        $schema->create('anggota', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_anggota')->primary();
            $table->string('nama_depan');
            $table->string('nama_belakang');
            $table->string('gelar_depan')->nullable();
            $table->string('gelar_belakang')->nullable();
            $table->string('jabatan');
            $table->string('status_pernikahan');
            $table->unsignedInteger('jumlah_anak')->default(0);
        });

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

    public function test_store_rejects_mismatched_jabatan(): void
    {
        $this->withoutMiddleware([JwtMiddleware::class, RoleMiddleware::class]);

        DB::table('anggota')->insert([
            'id_anggota' => 10,
            'nama_depan' => 'Rizki',
            'nama_belakang' => 'Saputra',
            'jabatan' => 'Anggota',
            'status_pernikahan' => 'Belum Kawin',
            'jumlah_anak' => 0,
        ]);

        DB::table('komponen_gaji')->insert([
            'id_komponen_gaji' => 500,
            'nama_komponen' => 'Tunjangan Ketua',
            'kategori' => 'Tunjangan Lain',
            'jabatan' => 'Ketua',
            'nominal' => 1000000,
            'satuan' => 'Bulan',
        ]);

        $response = $this->postJson('/api/admin/penggajian', [
            'id_anggota' => 10,
            'komponen_gaji_ids' => [500],
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Komponen gaji "Tunjangan Ketua" tidak dapat diberikan ke jabatan Anggota.']);
    }

    public function test_store_rejects_duplicate_entry(): void
    {
        $this->withoutMiddleware([JwtMiddleware::class, RoleMiddleware::class]);

        DB::table('anggota')->insert([
            'id_anggota' => 11,
            'nama_depan' => 'Sari',
            'nama_belakang' => 'Utami',
            'jabatan' => 'Anggota',
            'status_pernikahan' => 'Kawin',
            'jumlah_anak' => 1,
        ]);

        DB::table('komponen_gaji')->insert([
            'id_komponen_gaji' => 501,
            'nama_komponen' => 'Gaji Pokok Anggota',
            'kategori' => 'Gaji Pokok',
            'jabatan' => 'Anggota',
            'nominal' => 4000000,
            'satuan' => 'Bulan',
        ]);

        DB::table('penggajian')->insert([
            'id_anggota' => 11,
            'id_komponen_gaji' => 501,
        ]);

        $response = $this->postJson('/api/admin/penggajian', [
            'id_anggota' => 11,
            'komponen_gaji_ids' => [501],
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['duplicate_components' => [501]]);
    }

    public function test_store_inserts_records_and_returns_summary(): void
    {
        $this->withoutMiddleware([JwtMiddleware::class, RoleMiddleware::class]);

        $this->seedAllowances();

        DB::table('anggota')->insert([
            'id_anggota' => 12,
            'nama_depan' => 'Bima',
            'nama_belakang' => 'Satriya',
            'jabatan' => 'Wakil Ketua',
            'status_pernikahan' => 'Kawin',
            'jumlah_anak' => 1,
        ]);

        DB::table('komponen_gaji')->insert([
            'id_komponen_gaji' => 600,
            'nama_komponen' => 'Gaji Pokok Wakil Ketua',
            'kategori' => 'Gaji Pokok',
            'jabatan' => 'Wakil Ketua',
            'nominal' => 4600000,
            'satuan' => 'Bulan',
        ]);

        $response = $this->postJson('/api/admin/penggajian', [
            'id_anggota' => 12,
            'komponen_gaji_ids' => [600],
        ]);

        $response->assertCreated()
            ->assertJsonPath('summary.take_home_pay', 4600000 + 420000 + 168000)
            ->assertJsonPath('summary.total_bulanan', 4600000)
            ->assertJsonPath('summary.tunjangan_pasangan', 420000)
            ->assertJsonPath('summary.tunjangan_anak', 168000);

        $this->assertDatabaseHas('penggajian', [
            'id_anggota' => 12,
            'id_komponen_gaji' => 600,
        ]);
    }

    public function test_index_calculates_take_home_pay_for_various_cases(): void
    {
        $this->withoutMiddleware([JwtMiddleware::class, RoleMiddleware::class]);

        $this->seedAllowances();

        DB::table('komponen_gaji')->insert([
            [
                'id_komponen_gaji' => 700,
                'nama_komponen' => 'Gaji Pokok Ketua',
                'kategori' => 'Gaji Pokok',
                'jabatan' => 'Ketua',
                'nominal' => 5000000,
                'satuan' => 'Bulan',
            ],
            [
                'id_komponen_gaji' => 701,
                'nama_komponen' => 'Gaji Pokok Wakil Ketua',
                'kategori' => 'Gaji Pokok',
                'jabatan' => 'Wakil Ketua',
                'nominal' => 4600000,
                'satuan' => 'Bulan',
            ],
            [
                'id_komponen_gaji' => 702,
                'nama_komponen' => 'Gaji Pokok Anggota',
                'kategori' => 'Gaji Pokok',
                'jabatan' => 'Anggota',
                'nominal' => 4200000,
                'satuan' => 'Bulan',
            ],
            [
                'id_komponen_gaji' => 703,
                'nama_komponen' => 'Uang Sidang',
                'kategori' => 'Tunjangan Melekat',
                'jabatan' => 'Semua',
                'nominal' => 1000000,
                'satuan' => 'Bulan',
            ],
        ]);

        DB::table('anggota')->insert([
            [
                'id_anggota' => 20,
                'nama_depan' => 'Adi',
                'nama_belakang' => 'Nugroho',
                'jabatan' => 'Ketua',
                'status_pernikahan' => 'Belum Kawin',
                'jumlah_anak' => 0,
            ],
            [
                'id_anggota' => 21,
                'nama_depan' => 'Lina',
                'nama_belakang' => 'Pertiwi',
                'jabatan' => 'Wakil Ketua',
                'status_pernikahan' => 'Kawin',
                'jumlah_anak' => 1,
            ],
            [
                'id_anggota' => 22,
                'nama_depan' => 'Gilang',
                'nama_belakang' => 'Saputra',
                'jabatan' => 'Anggota',
                'status_pernikahan' => 'Kawin',
                'jumlah_anak' => 3,
            ],
        ]);

        DB::table('penggajian')->insert([
            ['id_anggota' => 20, 'id_komponen_gaji' => 700],
            ['id_anggota' => 20, 'id_komponen_gaji' => 703],
            ['id_anggota' => 21, 'id_komponen_gaji' => 701],
            ['id_anggota' => 21, 'id_komponen_gaji' => 703],
            ['id_anggota' => 22, 'id_komponen_gaji' => 702],
            ['id_anggota' => 22, 'id_komponen_gaji' => 703],
        ]);

        $response = $this->getJson('/api/admin/penggajian?per_page=10');

        $response->assertOk();

        $data = collect($response->json('data'))->keyBy('id_anggota');

        $this->assertEquals(6000000.0, $data[20]['take_home_pay']);
        $this->assertEquals(4600000.0 + 1000000.0 + 420000.0 + 168000.0, $data[21]['take_home_pay']);
        $this->assertEquals(4200000.0 + 1000000.0 + 420000.0 + (2 * 168000.0), $data[22]['take_home_pay']);
    }

    public function test_show_returns_component_detail(): void
    {
        $this->withoutMiddleware([JwtMiddleware::class, RoleMiddleware::class]);

        $this->seedAllowances();

        DB::table('anggota')->insert([
            'id_anggota' => 30,
            'nama_depan' => 'Rani',
            'nama_belakang' => 'Putri',
            'jabatan' => 'Anggota',
            'status_pernikahan' => 'Kawin',
            'jumlah_anak' => 2,
        ]);

        DB::table('komponen_gaji')->insert([
            [
                'id_komponen_gaji' => 800,
                'nama_komponen' => 'Gaji Pokok Anggota',
                'kategori' => 'Gaji Pokok',
                'jabatan' => 'Anggota',
                'nominal' => 4200000,
                'satuan' => 'Bulan',
            ],
            [
                'id_komponen_gaji' => 801,
                'nama_komponen' => 'Biaya Operasional',
                'kategori' => 'Tunjangan Lain',
                'jabatan' => 'Semua',
                'nominal' => 1500000,
                'satuan' => 'Bulan',
            ],
        ]);

        DB::table('penggajian')->insert([
            ['id_anggota' => 30, 'id_komponen_gaji' => 800],
            ['id_anggota' => 30, 'id_komponen_gaji' => 801],
        ]);

        $response = $this->getJson('/api/admin/penggajian/30');

        $response->assertOk()
            ->assertJsonPath('summary.jumlah_komponen', 2)
            ->assertJsonCount(2, 'komponen_gaji');
    }

    private function seedAllowances(): void
    {
        DB::table('komponen_gaji')->insert([
            [
                'id_komponen_gaji' => 900,
                'nama_komponen' => 'Tunjangan Istri/Suami',
                'kategori' => 'Tunjangan Melekat',
                'jabatan' => 'Semua',
                'nominal' => 420000,
                'satuan' => 'Bulan',
            ],
            [
                'id_komponen_gaji' => 901,
                'nama_komponen' => 'Tunjangan Anak',
                'kategori' => 'Tunjangan Melekat',
                'jabatan' => 'Semua',
                'nominal' => 168000,
                'satuan' => 'Bulan',
            ],
        ]);
    }
}
