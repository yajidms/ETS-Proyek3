'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://127.0.0.1:8000';
const TOKEN_STORAGE_KEY = 'jwt_token';

export default function LoginPage() {
  const [identifier, setIdentifier] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const router = useRouter();

  const handleSubmit = async (event) => {
    event.preventDefault();
    setLoading(true);
    setError('');

    try {
      const response = await fetch(`${API_BASE_URL}/api/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ username: identifier, password }),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Login gagal');
      }

      if (!data.token) {
        throw new Error('Token tidak ditemukan pada respons');
      }

      localStorage.setItem(TOKEN_STORAGE_KEY, data.token);
      if (data.expires_in) {
        localStorage.setItem(`${TOKEN_STORAGE_KEY}_exp`, String(Date.now() + Number(data.expires_in) * 1000));
      }
      if (data.user?.role) {
        localStorage.setItem(`${TOKEN_STORAGE_KEY}_role`, data.user.role);
      }

      if (data.user?.role === 'Admin') {
        router.push('/admin');
      } else {
        router.push('/public');
      }
    } catch (err) {
      setError(err.message ?? 'Terjadi kesalahan');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="page page--login">
      <div className="card card--login">
        <h1 className="card__title">Masuk ke Portal DPR</h1>
        <form className="form" onSubmit={handleSubmit}>
          <label className="form__label" htmlFor="username">
            Username atau Email
            <input
              id="username"
              className="form__input"
              value={identifier}
              onChange={(event) => setIdentifier(event.target.value)}
              placeholder="contoh: admin01 atau admin@example.com"
              autoComplete="username"
              required
            />
          </label>

          <label className="form__label" htmlFor="password">
            Password
            <input
              id="password"
              type="password"
              className="form__input"
              value={password}
              onChange={(event) => setPassword(event.target.value)}
              autoComplete="current-password"
              required
            />
          </label>

          {error && <p className="form__error">{error}</p>}

          <button className="form__button" type="submit" disabled={loading}>
            {loading ? 'Memproses…' : 'Masuk'}
          </button>
        </form>
      </div>
    </div>
  );
}
