# ETS Proyek – Akademik Platform

Kombinasi backend Laravel (folder `backend/`) dan frontend Next.js (folder `frontend/`) untuk sistem informasi akademik sederhana. Backend menyediakan autentikasi JWT, manajemen mata kuliah, dan data pendaftaran mahasiswa. Frontend menangani antarmuka web modern berbasis React/Next.js dan berkomunikasi dengan backend melalui API terproteksi.

## Struktur Folder

```
backend/   # Aplikasi Laravel 12 + PostgreSQL + JWT + spatie/permission
frontend/  # Aplikasi Next.js 15 App Router sebagai dashboard admin & mahasiswa
```

## Prasyarat

- PHP 8.2+ dengan Composer
- Node.js 18+ dan npm
- PostgreSQL (atau sesuaikan koneksi di `.env` Laravel)
- PowerShell (instruksi perintah di bawah sesuai dengan PowerShell standar Windows)

## Konfigurasi Backend (Laravel)

1. Masuk ke folder backend dan pasang dependensi:
   ```powershell
   cd d:\mission\ets-proyek\backend
   composer install
   npm install
   ```

2. Salin file environment dan isi variabel penting:
   ```powershell
   copy .env.example .env
   ```
   Perbarui nilai berikut agar sesuai dengan server basis data lokal:
   - `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
   - `JWT_SECRET` (gunakan kunci acak yang kuat)
   - `FRONTEND_URL` (default `http://localhost:3000`)

3. Jalankan key generate, migrasi, dan seeder agar akun contoh tersedia:
   ```powershell
   php artisan key:generate
   php artisan migrate --seed
   ```

4. Jalankan server pengembangan Laravel (dan Vite jika ingin mengakses Blade bawaan):
   ```powershell
   php artisan serve
   ```
   Secara default API berjalan di `http://127.0.0.1:8000`.

## Konfigurasi Frontend (Next.js)

1. Masuk ke folder frontend dan pasang dependensi:
   ```powershell
   cd d:\mission\ets-proyek\frontend
   npm install
   ```

2. Salin environment dan ubah URL backend jika perlu:
   ```powershell
   copy .env.example .env
   ```
   - `BACKEND_URL` harus menunjuk ke alamat Laravel (`http://127.0.0.1:8000`).

3. Jalankan pengembangan Next.js:
   ```powershell
   npm run dev
   ```
   Aplikasi dapat diakses di `http://localhost:3000`.


## Pengujian

- Backend: jalankan `php artisan test` dari folder `backend/` untuk memastikan endpoint API berfungsi (terdapat uji fitur login JWT).
- Frontend: jalankan `npm run lint` dari folder `frontend/` untuk memastikan kode TypeScript bersih.

## Catatan Tambahan

- Jika frontend dan backend dijalankan di host/port berbeda, pastikan `FRONTEND_URL` pada Laravel dan `BACKEND_URL` pada Next.js disesuaikan.
- Token JWT memiliki masa berlaku 1 jam; frontend otomatis membersihkan cookie ketika token kedaluwarsa.
- Endpoint admin (`/api/courses`, `/api/users`) membutuhkan peran `admin`, sedangkan `/api/enrollments` hanya dapat diakses oleh peran `student`.
