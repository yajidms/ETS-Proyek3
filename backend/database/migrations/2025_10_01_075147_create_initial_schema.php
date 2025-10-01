<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("CREATE TYPE role_pengguna AS ENUM ('Admin', 'Public')");
        DB::statement("CREATE TYPE jabatan_anggota AS ENUM ('Ketua', 'Wakil Ketua', 'Anggota')");
        DB::statement("CREATE TYPE status_pernikahan AS ENUM ('Kawin', 'Belum Kawin', 'Cerai Hidup', 'Cerai Mati')");
        DB::statement("CREATE TYPE kategori_komponen AS ENUM ('Gaji Pokok', 'Tunjangan Melekat', 'Tunjangan Lain')");
        DB::statement("CREATE TYPE jabatan_komponen AS ENUM ('Ketua', 'Wakil Ketua', 'Anggota', 'Semua')");
        DB::statement("CREATE TYPE satuan_komponen AS ENUM ('Bulan', 'Hari', 'Periode')");

        DB::statement(<<<'SQL'
            CREATE TABLE pengguna (
                id_pengguna BIGINT PRIMARY KEY,
                username VARCHAR(15) UNIQUE NOT NULL,
                password VARCHAR(128) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                nama_depan VARCHAR(100) NOT NULL,
                nama_belakang VARCHAR(100) NOT NULL,
                role role_pengguna
            )
        SQL);

        DB::statement("COMMENT ON COLUMN pengguna.password IS 'Hashed Password'");

        DB::statement(<<<'SQL'
            CREATE TABLE anggota (
                id_anggota BIGINT PRIMARY KEY,
                nama_depan VARCHAR(100) NOT NULL,
                nama_belakang VARCHAR(100) NOT NULL,
                gelar_depan VARCHAR(50),
                gelar_belakang VARCHAR(50),
                jabatan jabatan_anggota,
                status_pernikahan status_pernikahan
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE komponen_gaji (
                id_komponen_gaji BIGINT PRIMARY KEY,
                nama_komponen VARCHAR(100) NOT NULL,
                kategori kategori_komponen,
                jabatan jabatan_komponen,
                nominal NUMERIC(17,2) NOT NULL,
                satuan satuan_komponen NOT NULL
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE penggajian (
                id_komponen_gaji BIGINT,
                id_anggota BIGINT,
                PRIMARY KEY (id_komponen_gaji, id_anggota),
                CONSTRAINT penggajian_id_komponen_gaji_foreign FOREIGN KEY (id_komponen_gaji) REFERENCES komponen_gaji (id_komponen_gaji),
                CONSTRAINT penggajian_id_anggota_foreign FOREIGN KEY (id_anggota) REFERENCES anggota (id_anggota)
            )
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penggajian');
        Schema::dropIfExists('komponen_gaji');
        Schema::dropIfExists('anggota');
        Schema::dropIfExists('pengguna');

        DB::statement('DROP TYPE IF EXISTS satuan_komponen');
        DB::statement('DROP TYPE IF EXISTS jabatan_komponen');
        DB::statement('DROP TYPE IF EXISTS kategori_komponen');
        DB::statement('DROP TYPE IF EXISTS status_pernikahan');
        DB::statement('DROP TYPE IF EXISTS jabatan_anggota');
        DB::statement('DROP TYPE IF EXISTS role_pengguna');
    }
};
