## Frontend – Portal Transparansi Gaji DPR

Frontend sederhana berbasis Next.js (JavaScript) yang fokus pada halaman login dan tampilan placeholder admin. JWT disimpan di `localStorage` dan digunakan untuk mengarahkan pengguna sesuai peran.

### Konfigurasi

1. Pasang dependensi dan jalankan server pengembangan:
   ```powershell
   npm install
   npm run dev
   ```

2. Buka [http://localhost:3000](http://localhost:3000) untuk mengakses halaman login.

### Scripts Penting

| Perintah        | Deskripsi                                         |
|-----------------|----------------------------------------------------|
| `npm run dev`   | Menjalankan Next.js di mode pengembangan            |
| `npm run build` | Membuat build produksi                              |
| `npm run start` | Menjalankan build produksi                          |
| `npm run lint`  | Menjalankan ESLint untuk kode JavaScript            |

### Catatan Integrasi
- Token JWT dan metadata (peran, kedaluwarsa) disimpan di `localStorage` setelah login berhasil.
- Halaman admin memverifikasi token dan peran sebelum menampilkan konten placeholder.
- Logout dapat dilakukan dengan menghapus item `jwt_token*` di `localStorage` (akan ditambahkan ke UI pada iterasi berikutnya).
 