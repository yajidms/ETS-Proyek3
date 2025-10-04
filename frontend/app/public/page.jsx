'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://127.0.0.1:8000';
const DEFAULT_PER_PAGE = 10;
const TOKEN_KEY = 'jwt_token';

function formatCurrency(value) {
  const number = Number(value ?? 0);
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(number);
}

export default function PublicPayrollPage() {
  const router = useRouter();
  const [items, setItems] = useState([]);
  const [meta, setMeta] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [searchTerm, setSearchTerm] = useState('');
  const [perPage, setPerPage] = useState(DEFAULT_PER_PAGE);
  const [detail, setDetail] = useState(null);
  const [detailLoading, setDetailLoading] = useState(false);
  const [hasSession, setHasSession] = useState(false);

  useEffect(() => {
    if (typeof window !== 'undefined') {
      setHasSession(Boolean(localStorage.getItem(TOKEN_KEY)));
    }
  }, []);

  useEffect(() => {
    loadData(1);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [perPage]);

  async function loadData(page = 1, keyword = searchTerm) {
    setLoading(true);
    setError('');

    try {
      const params = new URLSearchParams();
      params.set('page', page.toString());
      params.set('per_page', perPage.toString());
      if (keyword.trim() !== '') {
        params.set('search', keyword.trim());
      }

      const response = await fetch(`${API_BASE_URL}/api/public/anggota?${params.toString()}`);
      if (!response.ok) {
        throw new Error(`Gagal memuat data publik (${response.status}).`);
      }

      const json = await response.json();
      setItems(json.data ?? []);
      setMeta(json.meta ?? null);
    } catch (err) {
      setError(err.message ?? 'Terjadi kesalahan saat memuat data.');
    } finally {
      setLoading(false);
    }
  }

  async function fetchDetail(id) {
    setDetailLoading(true);
    setError('');

    try {
      const response = await fetch(`${API_BASE_URL}/api/public/penggajian/${id}`);
      if (!response.ok) {
        if (response.status === 404) {
          throw new Error('Detail penggajian tidak ditemukan.');
        }
        throw new Error(`Gagal memuat detail (${response.status}).`);
      }

      const json = await response.json();
      setDetail(json);
    } catch (err) {
      setError(err.message ?? 'Terjadi kesalahan saat memuat detail.');
    } finally {
      setDetailLoading(false);
    }
  }

  function handleSearch(event) {
    event.preventDefault();
    loadData(1, searchTerm);
  }

  function handlePageChange(newPage) {
    if (meta && newPage >= 1 && newPage <= meta.last_page) {
      loadData(newPage);
    }
  }

  function handleSignOut() {
    if (typeof window === 'undefined') return;
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(`${TOKEN_KEY}_role`);
    localStorage.removeItem(`${TOKEN_KEY}_exp`);
    setHasSession(false);
    router.replace('/login');
  }

  return (
    <div className="public-portal">
      <header className="public-portal__hero">
        <div>
          <h1>Portal Transparansi Penggajian DPR</h1>
          <p>
            Jelajahi ringkasan take home pay anggota DPR secara terbuka. Data ini bersumber langsung dari
            sistem administrasi internal dan diperbarui secara berkala.
          </p>
        </div>
        {hasSession && (
          <button type="button" className="button button--ghost" onClick={handleSignOut}>
            Keluar dari Sesi Saya
          </button>
        )}
      </header>

      <section className="public-portal__section">
        <form className="filter" onSubmit={handleSearch}>
          <div className="filter__group">
            <label className="filter__label" htmlFor="search">Pencarian</label>
            <input
              id="search"
              className="filter__input"
              placeholder="Cari nama, jabatan, atau nominal THP"
              value={searchTerm}
              onChange={(event) => setSearchTerm(event.target.value)}
            />
          </div>

          <div className="filter__group">
            <label className="filter__label" htmlFor="perPage">Data per halaman</label>
            <select
              id="perPage"
              className="filter__input"
              value={perPage}
              onChange={(event) => setPerPage(Number(event.target.value))}
            >
              {[5, 10, 15, 20].map((option) => (
                <option key={option} value={option}>{option}</option>
              ))}
            </select>
          </div>

          <button type="submit" className="button button--primary">Terapkan</button>
        </form>
      </section>

      {error && (
        <section className="public-portal__section">
          <div className="alert alert--error">{error}</div>
        </section>
      )}

      <section className="public-portal__section">
        <div className="table-wrapper">
          <div className="table-header">
            <h2 className="table-title">Ringkasan Take Home Pay</h2>
            <span className="table-status">
              {loading ? 'Memuat data…' : `Menampilkan ${items.length} dari ${meta?.total ?? items.length} anggota`}
            </span>
          </div>

          <div className="table-scroll">
            <table className="table">
              <thead>
                <tr>
                  <th>ID Anggota</th>
                  <th>Nama Lengkap</th>
                  <th>Gelar</th>
                  <th>Jabatan</th>
                  <th>Status</th>
                  <th>Jumlah Anak</th>
                  <th>Jumlah Komponen</th>
                  <th>Total Bulanan</th>
                  <th>Take Home Pay</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                {loading ? (
                  <tr>
                    <td colSpan={10} className="table__loading">Memuat data…</td>
                  </tr>
                ) : items.length === 0 ? (
                  <tr>
                    <td colSpan={10} className="table__empty">Belum ada data publik untuk ditampilkan.</td>
                  </tr>
                ) : (
                  items.map((row) => (
                    <tr key={row.id_anggota}>
                      <td>{row.id_anggota}</td>
                      <td className="table-name">
                        <span className="table-name__primary">{row.nama_depan} {row.nama_belakang}</span>
                        <span className="table-name__secondary">{row.gelar_depan ?? '-'} {row.gelar_belakang ?? ''}</span>
                      </td>
                      <td>{[row.gelar_depan, row.gelar_belakang].filter(Boolean).join(' / ') || '-'}</td>
                      <td>{row.jabatan}</td>
                      <td>{row.status_pernikahan}</td>
                      <td>{row.jumlah_anak}</td>
                      <td>{row.jumlah_komponen}</td>
                      <td>{formatCurrency(row.total_bulanan)}</td>
                      <td>{formatCurrency(row.take_home_pay)}</td>
                      <td>
                        <button
                          type="button"
                          className="button button--ghost button--small"
                          onClick={() => fetchDetail(row.id_anggota)}
                        >
                          Lihat Detail
                        </button>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>

          {meta && meta.last_page > 1 && (
            <div className="pagination">
              <button
                type="button"
                className="button button--ghost button--small"
                disabled={meta.current_page === 1}
                onClick={() => handlePageChange(meta.current_page - 1)}
              >
                Sebelumnya
              </button>
              <span className="pagination__info">Halaman {meta.current_page} dari {meta.last_page}</span>
              <button
                type="button"
                className="button button--ghost button--small"
                disabled={meta.current_page === meta.last_page}
                onClick={() => handlePageChange(meta.current_page + 1)}
              >
                Berikutnya
              </button>
            </div>
          )}
        </div>
      </section>

      {detail && (
        <section className="public-portal__section">
          <div className="card card--wide">
            <div className="card__header">
              <h2 className="card__title">Detail Penggajian #{detail.anggota.id_anggota}</h2>
              <button type="button" className="button button--ghost button--small" onClick={() => setDetail(null)}>
                Tutup
              </button>
            </div>

            {detailLoading ? (
              <p>Memuat detail…</p>
            ) : (
              <>
                <p>
                  {detail.anggota.gelar_depan ? `${detail.anggota.gelar_depan} ` : ''}
                  {detail.anggota.nama_depan} {detail.anggota.nama_belakang}
                  {detail.anggota.gelar_belakang ? `, ${detail.anggota.gelar_belakang}` : ''}
                </p>
                <p>
                  Jabatan: <strong>{detail.anggota.jabatan}</strong> • Status:{' '}
                  <strong>{detail.anggota.status_pernikahan}</strong> • Jumlah anak:{' '}
                  <strong>{detail.anggota.jumlah_anak}</strong>
                </p>

                <div className="summary-grid">
                  <div className="summary-card">
                    <span>Total Komponen</span>
                    <strong>{detail.summary.jumlah_komponen}</strong>
                  </div>
                  <div className="summary-card">
                    <span>Total Bulanan</span>
                    <strong>{formatCurrency(detail.summary.total_bulanan)}</strong>
                  </div>
                  <div className="summary-card">
                    <span>Tunjangan Pasangan</span>
                    <strong>{formatCurrency(detail.summary.tunjangan_pasangan)}</strong>
                  </div>
                  <div className="summary-card">
                    <span>Tunjangan Anak</span>
                    <strong>{formatCurrency(detail.summary.tunjangan_anak)}</strong>
                  </div>
                  <div className="summary-card summary-card--accent">
                    <span>Take Home Pay</span>
                    <strong>{formatCurrency(detail.summary.take_home_pay)}</strong>
                  </div>
                </div>

                <h3 className="table__subtitle">Rincian Komponen</h3>
                <table className="table table--compact">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Nama Komponen</th>
                      <th>Kategori</th>
                      <th>Jabatan</th>
                      <th>Satuan</th>
                      <th>Nominal</th>
                    </tr>
                  </thead>
                  <tbody>
                    {detail.komponen_gaji.length === 0 ? (
                      <tr>
                        <td colSpan={6} className="table__empty">Belum ada komponen terdaftar.</td>
                      </tr>
                    ) : (
                      detail.komponen_gaji.map((row) => (
                        <tr key={row.id_komponen_gaji}>
                          <td>{row.id_komponen_gaji}</td>
                          <td>{row.nama_komponen}</td>
                          <td>{row.kategori}</td>
                          <td>{row.jabatan}</td>
                          <td>{row.satuan}</td>
                          <td>{formatCurrency(row.nominal)}</td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </>
            )}
          </div>
        </section>
      )}
    </div>
  );
}
