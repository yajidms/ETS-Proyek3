# ETS Proyek – DATA DPR

Aplikasi internal untuk mengelola data anggota DPR beserta rincian komponen gaji. Proyek terdiri dari backend Laravel (JWT + PostgreSQL) dan frontend Next.js 15 (App Router) yang bekerja sebagai portal admin dan publik.

## Struktur Folder

```
backend/   # Laravel 12 + PostgreSQL + JWT + middleware custom role
frontend/  # Next.js 15 (JavaScript) untuk dashboard admin & portal publik
```

## Fitur Utama

- **Autentikasi JWT** dengan login menggunakan username atau email, logout, dan blacklist token.
- **Manajemen Anggota DPR**: CRUD penuh, pencarian multi-kolom (ID, nama depan, nama belakang, jabatan), filter jabatan, dan agregasi total kompensasi per anggota.
- **Manajemen Komponen Gaji/Tunjangan**: CRUD penuh, validasi enum (kategori/jabatan/satuan), pencarian lintas kolom termasuk nominal, serta pembersihan relasi penggajian saat hapus.
- **Manajemen Penggajian**: Validasi jabatan-komponen, deteksi duplikasi, kalkulasi otomatis Take Home Pay (Tunjangan Pasangan & Anak), pencarian multi kolom hingga nominal THP, dan ringkasan detail komponen per anggota.
- **Portal Admin (Next.js)**: tabel dinamis, form tambah/ubah, konfirmasi hapus, filter & pagination dengan fetch API.
- **Portal Publik**: pengguna non-admin dapat melihat profilnya via endpoint `/api/me`.

## Prasyarat

- PHP 8.2+ dan Composer
- Node.js 18+ dan npm
- PostgreSQL (atau sesuaikan konfigurasi `.env` Laravel)
- PowerShell (instruksi terminal di bawah ditulis untuk Windows)

## Konfigurasi Backend (Laravel)

```powershell
cd d:\mission\ets-proyek1\backend
composer install
copy .env.example .env
```

Ubah variabel berikut pada `.env`:

- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `JWT_SECRET` (gunakan nilai acak, bisa dengan `php artisan jwt:secret` atau `php -r "echo bin2hex(random_bytes(32));"`)
- `FRONTEND_URL` (default `http://localhost:3000`)
- `APP_URL` bila ingin mengganti host backend

Lanjutkan dengan inisialisasi aplikasi dan data contoh:

```powershell
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Akun awal:

| Role   | Username | Email              | Password  |
|--------|----------|-------------------|-----------|
| Admin  | admin    | admin@example.com  | admin123  |
| Public | public   | public@example.com | public123 |

## Konfigurasi Frontend (Next.js)

```powershell
cd d:\mission\ets-proyek1\frontend
npm install
copy .env.example .env
```

- Set `NEXT_PUBLIC_API_URL` ke URL backend, contoh `http://127.0.0.1:8000`.

Jalankan pengembangan:

```powershell
npm run dev
```

Akses antarmuka di `http://localhost:3000`.

## Pengujian

- Backend: `php artisan test` (meng-cover login, CRUD komponen gaji, dsb.)
- Frontend: `npm run lint` (opsional, jika linting diaktifkan)

## Tips & Catatan

- Jalankan `php artisan migrate:fresh --seed` kapan pun ingin reset data contoh.
- Token JWT otomatis disimpan di frontend (localStorage) dan dibersihkan jika kedaluwarsa.
- Endpoint admin berada di `/api/admin/...` dan dilindungi middleware `jwt` + `role:Admin`.
- Untuk memuat dataset SQL eksternal (misal `proyek3-data-instance-gaji-dpr.sql`) ke PostgreSQL, jalankan perintah berikut:

	```powershell
	psql -h <DB_HOST> -U <DB_USER> -d <DB_NAME> -f d:\Downloads\Compressed\proyek3-data-instance-gaji-dpr.sql
	```

	Setelah import, jalankan `php artisan cache:clear` bila diperlukan untuk menyegarkan cache konfigurasi.