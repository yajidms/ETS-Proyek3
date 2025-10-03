'use client';

import { useEffect, useMemo, useState } from 'react';
import { useRouter } from 'next/navigation';

const TOKEN_KEY = 'jwt_token';
const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://127.0.0.1:8000';
const DEFAULT_PER_PAGE = 10;

const initialForm = {
  id_anggota: '',
  komponen_gaji_ids: [],
};

function formatCurrency(value) {
  const number = Number(value ?? 0);
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(number);
}

export default function PenggajianPage() {
  const router = useRouter();
  const [tokenChecked, setTokenChecked] = useState(false);
  const [items, setItems] = useState([]);
  const [meta, setMeta] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [searchTerm, setSearchTerm] = useState('');
  const [perPage, setPerPage] = useState(DEFAULT_PER_PAGE);
  const [formData, setFormData] = useState(initialForm);
  const [formErrors, setFormErrors] = useState({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [anggotaOptions, setAnggotaOptions] = useState([]);
  const [komponenOptions, setKomponenOptions] = useState([]);
  const [detail, setDetail] = useState(null);
  const [detailLoading, setDetailLoading] = useState(false);

  const authHeaders = useMemo(() => {
    if (typeof window === 'undefined') {
      return null;
    }

    const token = localStorage.getItem(TOKEN_KEY);
    if (!token) {
      return null;
    }

    return {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
    };
  }, [tokenChecked]);

  useEffect(() => {
    const token = localStorage.getItem(TOKEN_KEY);
    const role = localStorage.getItem(`${TOKEN_KEY}_role`);
    const exp = Number(localStorage.getItem(`${TOKEN_KEY}_exp`));

    if (!token || role !== 'Admin' || (exp && exp < Date.now())) {
      localStorage.removeItem(TOKEN_KEY);
      localStorage.removeItem(`${TOKEN_KEY}_role`);
      localStorage.removeItem(`${TOKEN_KEY}_exp`);
      router.replace('/login');
      return;
    }

    setTokenChecked(true);
  }, [router]);

  useEffect(() => {
    if (!tokenChecked) return;

    loadData(1);
    loadReferenceData();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [tokenChecked, perPage]);

  const selectedAnggota = useMemo(() => (
    anggotaOptions.find((option) => option.id_anggota === Number(formData.id_anggota)) ?? null
  ), [anggotaOptions, formData.id_anggota]);

  const filteredKomponen = useMemo(() => {
    if (!selectedAnggota) {
      return [];
    }

    return komponenOptions.filter((item) => (
      item.jabatan === 'Semua' || item.jabatan === selectedAnggota.jabatan
    ));
  }, [komponenOptions, selectedAnggota]);

  async function loadReferenceData() {
    if (!authHeaders) return;

    try {
      const params = new URLSearchParams({ per_page: '100' });
      const [anggotaRes, komponenRes] = await Promise.all([
        fetch(`${API_BASE_URL}/api/admin/anggota?${params.toString()}`, { headers: authHeaders }),
        fetch(`${API_BASE_URL}/api/admin/komponen-gaji?${params.toString()}`, { headers: authHeaders }),
      ]);

      if (anggotaRes.ok) {
        const json = await anggotaRes.json();
        setAnggotaOptions(json.data ?? []);
      }

      if (komponenRes.ok) {
        const json = await komponenRes.json();
        setKomponenOptions(json.data ?? []);
      }
    } catch (err) {
      setError(err.message ?? 'Terjadi kesalahan saat memuat referensi.');
    }
  }

  async function loadData(page = 1, keyword = searchTerm) {
    if (!authHeaders) return;

    setLoading(true);
    setError('');

    try {
      const params = new URLSearchParams();
      params.set('page', page.toString());
      params.set('per_page', perPage.toString());
      if (keyword.trim() !== '') {
        params.set('search', keyword.trim());
      }

      const response = await fetch(`${API_BASE_URL}/api/admin/penggajian?${params.toString()}`, {
        headers: authHeaders,
      });

      if (!response.ok) {
        throw new Error(`Gagal memuat data penggajian (${response.status}).`);
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

  function handleAnggotaChange(event) {
    setFormErrors({});
    setFormData({
      id_anggota: event.target.value,
      komponen_gaji_ids: [],
    });
  }

  function handleKomponenToggle(id) {
    setFormErrors((prev) => ({ ...prev, komponen_gaji_ids: undefined }));
    setFormData((prev) => {
      const exists = prev.komponen_gaji_ids.includes(id);
      return {
        ...prev,
        komponen_gaji_ids: exists
          ? prev.komponen_gaji_ids.filter((value) => value !== id)
          : [...prev.komponen_gaji_ids, id],
      };
    });
  }

  function validateForm() {
    const errors = {};
    if (!formData.id_anggota) {
      errors.id_anggota = 'Pilih salah satu anggota terlebih dahulu.';
    }
    if (!formData.komponen_gaji_ids.length) {
      errors.komponen_gaji_ids = 'Pilih minimal satu komponen gaji.';
    }
    return errors;
  }

  async function handleSubmit(event) {
    event.preventDefault();
    setError('');
    setFormErrors({});

    const errors = validateForm();
    if (Object.keys(errors).length > 0) {
      setFormErrors(errors);
      return;
    }

    if (!authHeaders) return;

    setIsSubmitting(true);

    try {
      const payload = {
        id_anggota: Number(formData.id_anggota),
        komponen_gaji_ids: formData.komponen_gaji_ids.map((value) => Number(value)),
      };

      const response = await fetch(`${API_BASE_URL}/api/admin/penggajian`, {
        method: 'POST',
        headers: authHeaders,
        body: JSON.stringify(payload),
      });

      if (response.status === 422) {
        const json = await response.json();
        setFormErrors(json.errors ?? {});
        if (json.message) {
          setError(json.message);
        }
        return;
      }

      if (!response.ok) {
        throw new Error(`Gagal menyimpan data (${response.status}).`);
      }

      const created = await response.json();
      await loadData(meta?.current_page ?? 1);
      setDetail(created);
      setFormData(initialForm);
    } catch (err) {
      setError(err.message ?? 'Terjadi kesalahan saat menyimpan data.');
    } finally {
      setIsSubmitting(false);
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

  async function fetchDetail(id) {
    if (!authHeaders) return;
    setDetailLoading(true);
    setError('');

    try {
      const response = await fetch(`${API_BASE_URL}/api/admin/penggajian/${id}`, { headers: authHeaders });
      if (!response.ok) {
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

  if (!tokenChecked) {
    return null;
  }

  return (
    <div className="admin">
      <header className="admin__header">
        <div>
          <h1 className="admin__title">Manajemen Penggajian DPR</h1>
          <p className="admin__subtitle">
            Susun komponen penghasilan, hitung otomatis Take Home Pay, dan telusuri rincian anggaran.
          </p>
        </div>
        <div className="admin__actions">
          <button
            type="button"
            className="button button--secondary"
            onClick={() => router.push('/admin')}
          >
            Kembali ke Dashboard
          </button>
          <button
            type="button"
            className="button button--ghost"
            onClick={() => {
              localStorage.removeItem(TOKEN_KEY);
              localStorage.removeItem(`${TOKEN_KEY}_role`);
              localStorage.removeItem(`${TOKEN_KEY}_exp`);
              router.replace('/login');
            }}
          >
            Keluar
          </button>
        </div>
      </header>

      <section className="admin__section">
        <form className="filter" onSubmit={handleSearch}>
          <div className="filter__group">
            <label htmlFor="search" className="filter__label">Pencarian</label>
            <input
              id="search"
              className="filter__input"
              placeholder="Cari nama, jabatan, ID anggota, atau nominal THP"
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

          <button type="submit" className="button button--primary">Cari</button>
        </form>
      </section>

      {error && (
        <section className="admin__section">
          <div className="alert alert--error">{error}</div>
        </section>
      )}

      <section className="admin__section admin__section--grid">
        <form className="form form--pane" onSubmit={handleSubmit}>
          <h2 className="form__title">Tambah Komponen Penggajian</h2>

          <label className="form__label" htmlFor="anggota">
            Pilih Anggota
            <select
              id="anggota"
              className="form__input"
              value={formData.id_anggota}
              onChange={handleAnggotaChange}
            >
              <option value="">-- Pilih Anggota --</option>
              {anggotaOptions.map((option) => (
                <option key={option.id_anggota} value={option.id_anggota}>
                  {`${option.id_anggota} - ${option.gelar_depan ? `${option.gelar_depan} ` : ''}${option.nama_depan} ${option.nama_belakang}${option.gelar_belakang ? `, ${option.gelar_belakang}` : ''} (${option.jabatan})`}
                </option>
              ))}
            </select>
            {formErrors.id_anggota && <span className="form__error">{formErrors.id_anggota}</span>}
          </label>

          <fieldset className="form__fieldset">
            <legend className="form__legend">Komponen Gaji yang Tersedia</legend>
            {!selectedAnggota && (
              <p className="form__hint">Pilih anggota untuk melihat daftar komponen yang sesuai jabatan.</p>
            )}
            {selectedAnggota && filteredKomponen.length === 0 && (
              <p className="form__hint">Belum ada komponen gaji yang cocok untuk jabatan ini.</p>
            )}
            <div className="form__checkbox-group">
              {filteredKomponen.map((item) => (
                <label key={item.id_komponen_gaji} className="form__checkbox">
                  <input
                    type="checkbox"
                    checked={formData.komponen_gaji_ids.includes(item.id_komponen_gaji)}
                    onChange={() => handleKomponenToggle(item.id_komponen_gaji)}
                  />
                  <span>
                    <strong>{item.nama_komponen}</strong>
                    <small>
                      {`${item.kategori} • ${item.jabatan} • ${item.satuan}`}
                    </small>
                    <small>{formatCurrency(item.nominal)}</small>
                  </span>
                </label>
              ))}
            </div>
            {formErrors.komponen_gaji_ids && (
              <span className="form__error">{formErrors.komponen_gaji_ids}</span>
            )}
          </fieldset>

          <button type="submit" className="button button--primary" disabled={isSubmitting}>
            {isSubmitting ? 'Menyimpan…' : 'Simpan Penggajian'}
          </button>
        </form>

        <div className="table-wrapper">
          <h2 className="table__title">Ringkasan Take Home Pay</h2>
          <table className="table">
            <thead>
              <tr>
                <th>ID Anggota</th>
                <th>Gelar Depan</th>
                <th>Nama Depan</th>
                <th>Nama Belakang</th>
                <th>Gelar Belakang</th>
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
                  <td colSpan={12} className="table__loading">Memuat data…</td>
                </tr>
              ) : items.length === 0 ? (
                <tr>
                  <td colSpan={12} className="table__empty">Belum ada data penggajian untuk ditampilkan.</td>
                </tr>
              ) : (
                items.map((row) => (
                  <tr key={row.id_anggota}>
                    <td>{row.id_anggota}</td>
                    <td>{row.gelar_depan ?? '-'}</td>
                    <td>{row.nama_depan}</td>
                    <td>{row.nama_belakang}</td>
                    <td>{row.gelar_belakang ?? '-'}</td>
                    <td>{row.jabatan}</td>
                    <td>{row.status_pernikahan}</td>
                    <td>{row.jumlah_anak}</td>
                    <td>{row.jumlah_komponen}</td>
                    <td>{formatCurrency(row.total_bulanan)}</td>
                    <td>{formatCurrency(row.take_home_pay)}</td>
                    <td>
                      <button
                        type="button"
                        className="button button--ghost"
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

          {meta && meta.last_page > 1 && (
            <div className="pagination">
              <button
                type="button"
                className="button button--ghost"
                disabled={meta.current_page === 1}
                onClick={() => handlePageChange(meta.current_page - 1)}
              >
                Sebelumnya
              </button>
              <span>Halaman {meta.current_page} dari {meta.last_page}</span>
              <button
                type="button"
                className="button button--ghost"
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
        <section className="admin__section">
          <div className="card">
            <div className="card__header">
              <h2 className="card__title">Detail Penggajian Anggota #{detail.anggota.id_anggota}</h2>
              <button
                type="button"
                className="button button--ghost"
                onClick={() => setDetail(null)}
              >
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

                <h3 className="table__subtitle">Daftar Komponen</h3>
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
