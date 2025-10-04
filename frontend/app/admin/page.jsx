'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';

const TOKEN_STORAGE_KEY = 'jwt_token';

export default function AdminPage() {
  const router = useRouter();
  const [status, setStatus] = useState('Memuat data admin…');

  useEffect(() => {
    const token = localStorage.getItem(TOKEN_STORAGE_KEY);
    const role = localStorage.getItem(`${TOKEN_STORAGE_KEY}_role`);
    const exp = Number(localStorage.getItem(`${TOKEN_STORAGE_KEY}_exp`));

    if (!token || role !== 'Admin' || (exp && exp < Date.now())) {
      setStatus('Akses ditolak. Mengalihkan ke halaman login…');
      localStorage.removeItem(TOKEN_STORAGE_KEY);
      localStorage.removeItem(`${TOKEN_STORAGE_KEY}_role`);
      localStorage.removeItem(`${TOKEN_STORAGE_KEY}_exp`);
      setTimeout(() => router.replace('/login'), 1200);
      return;
    }

    setStatus('Selamat datang, Admin. Kelola data anggota melalui tombol di bawah.');
  }, [router]);

  function handleLogout() {
    localStorage.removeItem(TOKEN_STORAGE_KEY);
    localStorage.removeItem(`${TOKEN_STORAGE_KEY}_role`);
    localStorage.removeItem(`${TOKEN_STORAGE_KEY}_exp`);
    router.replace('/login');
  }

  return (
    <div className="page page--centered">
      <div className="card">
        <h1 className="card__title">Dashboard Admin</h1>
        <p>{status}</p>
        <div className="admin__nav">
          <Link className="form__button" href="/admin/anggota">
            Kelola Anggota DPR
          </Link>
          <Link className="form__button" href="/admin/komponen-gaji">
            Kelola Komponen Gaji
          </Link>
          <Link className="form__button" href="/admin/penggajian">
            Kelola Penggajian
          </Link>
        </div>
        <div className="admin__actions" style={{ marginTop: '0.75rem', justifyContent: 'flex-end' }}>
          <button type="button" className="button button--ghost" onClick={handleLogout}>
            Keluar
          </button>
        </div>
      </div>
    </div>
  );
}
