<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class GajiDprSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('pengguna')->insert([
            [
                'id_pengguna' => 1,
                'username' => 'admin',
                'password' => Hash::make('admin123'),
                'email' => 'thoriq@simanjuntak.com',
                'nama_depan' => 'Thoriq',
                'nama_belakang' => 'Simanjuntak',
                'role' => 'Admin',
            ],
            [
                'id_pengguna' => 2,
                'username' => 'citizen',
                'password' => Hash::make('public123'),
                'email' => 'richard@subakat.com',
                'nama_depan' => 'Richard',
                'nama_belakang' => 'Subakat',
                'role' => 'Public',
            ],
        ]);

        DB::table('anggota')->insert([
            [
                'id_anggota' => 101,
                'nama_depan' => 'Puan',
                'nama_belakang' => 'Maharani',
                'gelar_depan' => 'Dr. (H.C.)',
                'gelar_belakang' => 'S.Sos.',
                'jabatan' => 'Ketua',
                'status_pernikahan' => 'Kawin',
                'jumlah_anak' => 2,
            ],
            [
                'id_anggota' => 102,
                'nama_depan' => 'Lodewijk',
                'nama_belakang' => 'Paulus',
                'gelar_depan' => null,
                'gelar_belakang' => null,
                'jabatan' => 'Wakil Ketua',
                'status_pernikahan' => 'Kawin',
                'jumlah_anak' => 3,
            ],
            [
                'id_anggota' => 103,
                'nama_depan' => 'Fadli',
                'nama_belakang' => 'Zon',
                'gelar_depan' => 'Dr.',
                'gelar_belakang' => 'S.S., M.Sc.',
                'jabatan' => 'Anggota',
                'status_pernikahan' => 'Kawin',
                'jumlah_anak' => 1,
            ],
            [
                'id_anggota' => 104,
                'nama_depan' => 'Sufmi',
                'nama_belakang' => 'Dasco',
                'gelar_depan' => 'Prof. Dr. Ir. H.',
                'gelar_belakang' => 'S.H., M.H.',
                'jabatan' => 'Wakil Ketua',
                'status_pernikahan' => 'Kawin',
                'jumlah_anak' => 2,
            ],
            [
                'id_anggota' => 105,
                'nama_depan' => 'Muhaimin',
                'nama_belakang' => 'Iskandar',
                'gelar_depan' => 'Dr (HC). Drs.',
                'gelar_belakang' => null,
                'jabatan' => 'Anggota',
                'status_pernikahan' => 'Kawin',
                'jumlah_anak' => 4,
            ],
            [
                'id_anggota' => 106,
                'nama_depan' => 'Herman',
                'nama_belakang' => 'Hery',
                'gelar_depan' => null,
                'gelar_belakang' => null,
                'jabatan' => 'Anggota',
                'status_pernikahan' => 'Belum Kawin',
                'jumlah_anak' => 0,
            ],
        ]);

        DB::table('komponen_gaji')->insert([
            ['id_komponen_gaji' => 201, 'nama_komponen' => 'Gaji Pokok Ketua', 'kategori' => 'Gaji Pokok', 'jabatan' => 'Ketua', 'nominal' => 5040000.00, 'satuan' => 'Bulan'],
            ['id_komponen_gaji' => 202, 'nama_komponen' => 'Gaji Pokok Wakil Ketua', 'kategori' => 'Gaji Pokok', 'jabatan' => 'Wakil Ketua', 'nominal' => 4620000.00, 'satuan' => 'Bulan'],
            ['id_komponen_gaji' => 203, 'nama_komponen' => 'Gaji Pokok Anggota', 'kategori' => 'Gaji Pokok', 'jabatan' => 'Anggota', 'nominal' => 4200000.00, 'satuan' => 'Bulan'],
            ['id_komponen_gaji' => 204, 'nama_komponen' => 'Tunjangan Istri/Suami', 'kategori' => 'Tunjangan Melekat', 'jabatan' => 'Semua', 'nominal' => 420000.00, 'satuan' => 'Bulan'],
            ['id_komponen_gaji' => 205, 'nama_komponen' => 'Tunjangan Anak', 'kategori' => 'Tunjangan Melekat', 'jabatan' => 'Semua', 'nominal' => 168000.00, 'satuan' => 'Bulan'],
            ['id_komponen_gaji' => 206, 'nama_komponen' => 'Uang Sidang/Paket', 'kategori' => 'Tunjangan Melekat', 'jabatan' => 'Semua', 'nominal' => 2000000.00, 'satuan' => 'Bulan'],
            ['id_komponen_gaji' => 207, 'nama_komponen' => 'Tunjangan Jabatan Ketua', 'kategori' => 'Tunjangan Melekat', 'jabatan' => 'Ketua', 'nominal' => 18900000.00, 'satuan' => 'Bulan'],
            ['id_komponen_gaji' => 208, 'nama_komponen' => 'Tunjangan Jabatan Wakil Ketua', 'kategori' => 'Tunjangan Melekat', 'jabatan' => 'Wakil Ketua', 'nominal' => 15600000.00, 'satuan' => 'Bulan'],
            ['id_komponen_gaji' => 209, 'nama_komponen' => 'Tunjangan Jabatan Anggota', 'kategori' => 'Tunjangan Melekat', 'jabatan' => 'Anggota', 'nominal' => 9700000.00, 'satuan' => 'Bulan'],
            ['id_komponen_gaji' => 210, 'nama_komponen' => 'Tunjangan Beras', 'kategori' => 'Tunjangan Melekat', 'jabatan' => 'Semua', 'nominal' => 12000000.00, 'satuan' => 'Bulan'],
            ['id_komponen_gaji' => 213, 'nama_komponen' => 'Tunjangan Kehormatan Ketua', 'kategori' => 'Tunjangan Lain', 'jabatan' => 'Ketua', 'nominal' => 6690000.00, 'satuan' => 'Bulan'],
            ['id_komponen_gaji' => 214, 'nama_komponen' => 'Tunjangan Kehormatan Wakil Ketua', 'kategori' => 'Tunjangan Lain', 'jabatan' => 'Wakil Ketua', 'nominal' => 6450000.00, 'satuan' => 'Bulan'],
            ['id_komponen_gaji' => 215, 'nama_komponen' => 'Tunjangan Kehormatan Anggota', 'kategori' => 'Tunjangan Lain', 'jabatan' => 'Anggota', 'nominal' => 5580000.00, 'satuan' => 'Bulan'],
            ['id_komponen_gaji' => 216, 'nama_komponen' => 'Tunjangan Komunikasi Ketua', 'kategori' => 'Tunjangan Lain', 'jabatan' => 'Ketua', 'nominal' => 16468000.00, 'satuan' => 'Bulan'],
            ['id_komponen_gaji' => 217, 'nama_komponen' => 'Tunjangan Komunikasi Wakil Ketua', 'kategori' => 'Tunjangan Lain', 'jabatan' => 'Wakil Ketua', 'nominal' => 16009000.00, 'satuan' => 'Bulan'],
            ['id_komponen_gaji' => 218, 'nama_komponen' => 'Tunjangan Komunikasi Anggota', 'kategori' => 'Tunjangan Lain', 'jabatan' => 'Anggota', 'nominal' => 15554000.00, 'satuan' => 'Bulan'],
            ['id_komponen_gaji' => 219, 'nama_komponen' => 'Tunjangan Fungsi Ketua', 'kategori' => 'Tunjangan Lain', 'jabatan' => 'Ketua', 'nominal' => 5250000.00, 'satuan' => 'Bulan'],
            ['id_komponen_gaji' => 220, 'nama_komponen' => 'Tunjangan Fungsi Wakil Ketua', 'kategori' => 'Tunjangan Lain', 'jabatan' => 'Wakil Ketua', 'nominal' => 4500000.00, 'satuan' => 'Bulan'],
            ['id_komponen_gaji' => 221, 'nama_komponen' => 'Tunjangan Fungsi Anggota', 'kategori' => 'Tunjangan Lain', 'jabatan' => 'Anggota', 'nominal' => 3750000.00, 'satuan' => 'Bulan'],
            ['id_komponen_gaji' => 222, 'nama_komponen' => 'Bantuan Listrik & Telepon', 'kategori' => 'Tunjangan Lain', 'jabatan' => 'Semua', 'nominal' => 7700000.00, 'satuan' => 'Bulan'],
            ['id_komponen_gaji' => 223, 'nama_komponen' => 'Asisten Anggota', 'kategori' => 'Tunjangan Lain', 'jabatan' => 'Semua', 'nominal' => 2250000.00, 'satuan' => 'Bulan'],
            ['id_komponen_gaji' => 224, 'nama_komponen' => 'Tunjangan Perumahan', 'kategori' => 'Tunjangan Lain', 'jabatan' => 'Semua', 'nominal' => 50000000.00, 'satuan' => 'Bulan'],
            ['id_komponen_gaji' => 225, 'nama_komponen' => 'Fasilitas Kredit Mobil', 'kategori' => 'Tunjangan Lain', 'jabatan' => 'Semua', 'nominal' => 70000000.00, 'satuan' => 'Periode'],
        ]);

        DB::table('penggajian')->insert([
            ['id_komponen_gaji' => 201, 'id_anggota' => 101], ['id_komponen_gaji' => 204, 'id_anggota' => 101],
            ['id_komponen_gaji' => 205, 'id_anggota' => 101], ['id_komponen_gaji' => 206, 'id_anggota' => 101],
            ['id_komponen_gaji' => 207, 'id_anggota' => 101], ['id_komponen_gaji' => 210, 'id_anggota' => 101],
            ['id_komponen_gaji' => 213, 'id_anggota' => 101], ['id_komponen_gaji' => 216, 'id_anggota' => 101],
            ['id_komponen_gaji' => 219, 'id_anggota' => 101], ['id_komponen_gaji' => 222, 'id_anggota' => 101],
            ['id_komponen_gaji' => 224, 'id_anggota' => 101], ['id_komponen_gaji' => 225, 'id_anggota' => 101],

            ['id_komponen_gaji' => 202, 'id_anggota' => 102], ['id_komponen_gaji' => 204, 'id_anggota' => 102],
            ['id_komponen_gaji' => 205, 'id_anggota' => 102], ['id_komponen_gaji' => 206, 'id_anggota' => 102],
            ['id_komponen_gaji' => 208, 'id_anggota' => 102], ['id_komponen_gaji' => 210, 'id_anggota' => 102],
            ['id_komponen_gaji' => 214, 'id_anggota' => 102], ['id_komponen_gaji' => 217, 'id_anggota' => 102],
            ['id_komponen_gaji' => 220, 'id_anggota' => 102], ['id_komponen_gaji' => 222, 'id_anggota' => 102],
            ['id_komponen_gaji' => 224, 'id_anggota' => 102], ['id_komponen_gaji' => 225, 'id_anggota' => 102],

            ['id_komponen_gaji' => 203, 'id_anggota' => 103], ['id_komponen_gaji' => 204, 'id_anggota' => 103],
            ['id_komponen_gaji' => 205, 'id_anggota' => 103], ['id_komponen_gaji' => 206, 'id_anggota' => 103],
            ['id_komponen_gaji' => 209, 'id_anggota' => 103], ['id_komponen_gaji' => 210, 'id_anggota' => 103],
            ['id_komponen_gaji' => 215, 'id_anggota' => 103], ['id_komponen_gaji' => 218, 'id_anggota' => 103],
            ['id_komponen_gaji' => 221, 'id_anggota' => 103], ['id_komponen_gaji' => 222, 'id_anggota' => 103],
            ['id_komponen_gaji' => 224, 'id_anggota' => 103], ['id_komponen_gaji' => 225, 'id_anggota' => 103],

            ['id_komponen_gaji' => 202, 'id_anggota' => 104], ['id_komponen_gaji' => 204, 'id_anggota' => 104],
            ['id_komponen_gaji' => 205, 'id_anggota' => 104], ['id_komponen_gaji' => 206, 'id_anggota' => 104],
            ['id_komponen_gaji' => 208, 'id_anggota' => 104], ['id_komponen_gaji' => 210, 'id_anggota' => 104],
            ['id_komponen_gaji' => 214, 'id_anggota' => 104], ['id_komponen_gaji' => 217, 'id_anggota' => 104],
            ['id_komponen_gaji' => 220, 'id_anggota' => 104], ['id_komponen_gaji' => 222, 'id_anggota' => 104],
            ['id_komponen_gaji' => 224, 'id_anggota' => 104], ['id_komponen_gaji' => 225, 'id_anggota' => 104],

            ['id_komponen_gaji' => 203, 'id_anggota' => 105], ['id_komponen_gaji' => 204, 'id_anggota' => 105],
            ['id_komponen_gaji' => 205, 'id_anggota' => 105], ['id_komponen_gaji' => 206, 'id_anggota' => 105],
            ['id_komponen_gaji' => 209, 'id_anggota' => 105], ['id_komponen_gaji' => 210, 'id_anggota' => 105],
            ['id_komponen_gaji' => 215, 'id_anggota' => 105], ['id_komponen_gaji' => 218, 'id_anggota' => 105],
            ['id_komponen_gaji' => 221, 'id_anggota' => 105], ['id_komponen_gaji' => 222, 'id_anggota' => 105],
            ['id_komponen_gaji' => 224, 'id_anggota' => 105], ['id_komponen_gaji' => 225, 'id_anggota' => 105],

            ['id_komponen_gaji' => 203, 'id_anggota' => 106], ['id_komponen_gaji' => 204, 'id_anggota' => 106],
            ['id_komponen_gaji' => 205, 'id_anggota' => 106], ['id_komponen_gaji' => 206, 'id_anggota' => 106],
            ['id_komponen_gaji' => 209, 'id_anggota' => 106], ['id_komponen_gaji' => 210, 'id_anggota' => 106],
            ['id_komponen_gaji' => 215, 'id_anggota' => 106], ['id_komponen_gaji' => 218, 'id_anggota' => 106],
            ['id_komponen_gaji' => 221, 'id_anggota' => 106], ['id_komponen_gaji' => 222, 'id_anggota' => 106],
            ['id_komponen_gaji' => 224, 'id_anggota' => 106], ['id_komponen_gaji' => 225, 'id_anggota' => 106],
        ]);
    }
}
