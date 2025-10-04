<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicPenggajianTest extends TestCase
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

    public function test_daftar_anggota_returns_take_home_pay_summary(): void
    {
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

        $response = $this->getJson('/api/public/anggota?per_page=10');

        $response->assertOk();

        $data = collect($response->json('data'))->keyBy('id_anggota');

        $this->assertEquals(6000000.0, $data[20]['take_home_pay']);
        $this->assertEquals(4600000.0 + 1000000.0 + 420000.0 + 168000.0, $data[21]['take_home_pay']);
        $this->assertEquals(4200000.0 + 1000000.0 + 420000.0 + (2 * 168000.0), $data[22]['take_home_pay']);
    }

    public function test_data_penggajian_returns_detail_summary(): void
    {
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

        $response = $this->getJson('/api/public/penggajian/30');

        $response->assertOk()
            ->assertJsonPath('summary.jumlah_komponen', 2)
            ->assertJsonCount(2, 'komponen_gaji');
    }

    public function test_data_penggajian_returns_not_found_for_missing_record(): void
    {
        $response = $this->getJson('/api/public/penggajian/999');

        $response->assertNotFound();
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
