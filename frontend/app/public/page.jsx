'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';

const TOKEN_KEY = 'jwt_token';

export default function PublicPage() {
  const router = useRouter();
  const [user, setUser] = useState(null);
  const [status, setStatus] = useState('Memuat profil…');
  const [error, setError] = useState('');

  useEffect(() => {
    if (typeof window === 'undefined') {
      return;
    }

    const token = localStorage.getItem(TOKEN_KEY);
    const role = localStorage.getItem(`${TOKEN_KEY}_role`);
    const exp = Number(localStorage.getItem(`${TOKEN_KEY}_exp`));

    if (!token || role !== 'Public' || (exp && exp < Date.now())) {
      setStatus('Sesi tidak valid. Mengalihkan ke login…');
      localStorage.removeItem(TOKEN_KEY);
      localStorage.removeItem(`${TOKEN_KEY}_role`);
      localStorage.removeItem(`${TOKEN_KEY}_exp`);
      setTimeout(() => router.replace('/login'), 1000);
      return;
    }

    const controller = new AbortController();
    const abortSignal = controller.signal;

    async function fetchProfile() {
      try {
        const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/api/me`, {
          headers: {
            Authorization: `Bearer ${token}`,
          },
          signal: abortSignal,
        });

        if (!response.ok) {
          throw new Error('Tidak dapat memuat profil.');
        }

        const data = await response.json();
        setUser(data);
        setStatus('Selamat datang di portal transparansi gaji DPR.');
      } catch (err) {
        if (err.name === 'AbortError') return;
        setError(err.message ?? 'Terjadi kesalahan saat memuat data.');
      }
    }

    fetchProfile();

    return () => controller.abort();
  }, [router]);

  const handleLogout = () => {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(`${TOKEN_KEY}_role`);
    localStorage.removeItem(`${TOKEN_KEY}_exp`);
    router.replace('/login');
  };

  return (
    <div className="page page--centered">
      <div className="card">
        <h1 className="card__title">Portal Publik DPR</h1>
        <p>{status}</p>
        {error && <p className="form__error">{error}</p>}

        {user && (
          <div className="form" style={{ marginTop: '1rem' }}>
            <div className="form__label">
              <span>Nama Lengkap</span>
              <strong>{`${user.nama_depan} ${user.nama_belakang}`}</strong>
            </div>
            <div className="form__label">
              <span>Username</span>
              <strong>{user.username}</strong>
            </div>
            <div className="form__label">
              <span>Peran</span>
              <strong>{user.role}</strong>
            </div>
          </div>
        )}

        <button type="button" className="form__button" onClick={handleLogout}>
          Keluar
        </button>
      </div>
    </div>
  );
}
