'use client';

import { useEffect, useMemo, useState } from 'react';
import { useRouter } from 'next/navigation';

const TOKEN_KEY = 'jwt_token';
const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://127.0.0.1:8000';
const KATEGORI_OPTIONS = ['Gaji Pokok', 'Tunjangan Melekat', 'Tunjangan Lain'];
const JABATAN_OPTIONS = ['Ketua', 'Wakil Ketua', 'Anggota', 'Semua'];
const SATUAN_OPTIONS = ['Bulan', 'Hari', 'Periode'];
const DEFAULT_PER_PAGE = 10;

const emptyForm = {
  id_komponen_gaji: '',
  nama_komponen: '',
  kategori: 'Gaji Pokok',
  jabatan: 'Semua',
  nominal: '',
  satuan: 'Bulan',
};

function formatCurrency(value) {
  const number = Number(value ?? 0);
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(number);
}

export default function KomponenGajiPage() {
  const router = useRouter();
  const [tokenChecked, setTokenChecked] = useState(false);
  const [items, setItems] = useState([]);
  const [meta, setMeta] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [formErrors, setFormErrors] = useState({});
  const [formData, setFormData] = useState(emptyForm);
  const [isEditing, setIsEditing] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [kategoriFilter, setKategoriFilter] = useState('');
  const [jabatanFilter, setJabatanFilter] = useState('');
  const [satuanFilter, setSatuanFilter] = useState('');
  const [perPage, setPerPage] = useState(DEFAULT_PER_PAGE);

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
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [tokenChecked, perPage, kategoriFilter, jabatanFilter, satuanFilter]);

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
      if (kategoriFilter) {
        params.set('kategori', kategoriFilter);
      }
      if (jabatanFilter) {
        params.set('jabatan', jabatanFilter);
      }
      if (satuanFilter) {
        params.set('satuan', satuanFilter);
      }

      const response = await fetch(`${API_BASE_URL}/api/admin/komponen-gaji?${params.toString()}`, {
        headers: authHeaders,
      });

      if (!response.ok) {
        throw new Error(`Gagal memuat data komponen gaji (${response.status})`);
      }

      const json = await response.json();
      setItems(json.data ?? []);
      setMeta(json.meta ?? null);
    } catch (err) {
      setError(err.message ?? 'Terjadi kesalahan saat memuat data');
    } finally {
      setLoading(false);
    }
  }

  function handleInputChange(event) {
    const { name, value } = event.target;

    setFormData((prev) => ({
      ...prev,
      [name]: name === 'nominal' ? value.replace(/[^0-9.,-]/g, '') : value,
    }));
  }

  function resetForm() {
    setFormData(emptyForm);
    setIsEditing(false);
    setFormErrors({});
  }

  function validateForm(validateId = true) {
    const errors = {};
    const trimmedName = formData.nama_komponen.trim();
    const nominalValue = String(formData.nominal).replace(/,/g, '.');

    if (validateId) {
      const idValue = Number(formData.id_komponen_gaji);
      if (!idValue || !Number.isInteger(idValue) || idValue <= 0) {
        errors.id_komponen_gaji = 'ID komponen wajib diisi dan harus bilangan bulat positif.';
      }
    }

    if (!trimmedName) {
      errors.nama_komponen = 'Nama komponen wajib diisi.';
    }

    const numericNominal = Number(nominalValue);
    if (Number.isNaN(numericNominal) || numericNominal < 0) {
      errors.nominal = 'Nominal wajib diisi dan tidak boleh negatif.';
    }

    return { errors, nominal: Number.isNaN(numericNominal) ? 0 : numericNominal, trimmedName };
  }

  async function handleSubmit(event) {
    event.preventDefault();
    setError('');
    setFormErrors({});

    const { errors, nominal, trimmedName } = validateForm(!isEditing);
    if (Object.keys(errors).length > 0) {
      setFormErrors(errors);
      return;
    }

    const payload = {
      nama_komponen: trimmedName,
      kategori: formData.kategori,
      jabatan: formData.jabatan,
      nominal,
      satuan: formData.satuan,
    };

    let url = `${API_BASE_URL}/api/admin/komponen-gaji`;
    let method = 'POST';

    if (isEditing) {
      url = `${url}/${formData.id_komponen_gaji}`;
      method = 'PUT';
    } else {
      payload.id_komponen_gaji = Number(formData.id_komponen_gaji);
    }

    try {
      const response = await fetch(url, {
        method,
        headers: authHeaders,
        body: JSON.stringify(payload),
      });

      if (response.status === 422) {
        const json = await response.json();
        setFormErrors(json.errors ?? {});
        return;
      }

      if (!response.ok) {
        throw new Error(`Gagal menyimpan data (${response.status})`);
      }

      const targetPage = isEditing && meta ? meta.current_page : 1;
      await loadData(targetPage);
      resetForm();
    } catch (err) {
      setError(err.message ?? 'Terjadi kesalahan saat menyimpan data');
    }
  }

  function handleEdit(row) {
    setFormData({
      id_komponen_gaji: row.id_komponen_gaji,
      nama_komponen: row.nama_komponen ?? '',
      kategori: row.kategori ?? 'Gaji Pokok',
      jabatan: row.jabatan ?? 'Semua',
      nominal: row.nominal ?? '',
      satuan: row.satuan ?? 'Bulan',
    });
    setIsEditing(true);
    setFormErrors({});
  }

  async function handleDelete(id) {
    if (!window.confirm('Hapus komponen gaji ini?')) {
      return;
    }

    try {
      const response = await fetch(`${API_BASE_URL}/api/admin/komponen-gaji/${id}`, {
        method: 'DELETE',
        headers: authHeaders,
      });

      if (!response.ok && response.status !== 204) {
        throw new Error(`Gagal menghapus data (${response.status})`);
      }

      const targetPage = meta && items.length === 1 && meta.current_page > 1
        ? meta.current_page - 1
        : meta?.current_page ?? 1;

      await loadData(targetPage);
    } catch (err) {
      setError(err.message ?? 'Terjadi kesalahan saat menghapus data');
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

  if (!tokenChecked) {
    return null;
  }

  return (
    <div className="admin">
      <header className="admin__header">
        <div>
          <h1 className="admin__title">Manajemen Komponen Gaji &amp; Tunjangan</h1>
          <p className="admin__subtitle">Kelola struktur kompensasi DPR dengan cepat dan aman.</p>
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
            <label className="filter__label" htmlFor="search">Pencarian</label>
            <input
              id="search"
              name="search"
              className="filter__input"
              placeholder="Cari nama, kategori, jabatan, atau ID"
              value={searchTerm}
              onChange={(event) => setSearchTerm(event.target.value)}
            />
          </div>

          <div className="filter__group">
            <label className="filter__label" htmlFor="kategori">Kategori</label>
            <select
              id="kategori"
              name="kategori"
              className="filter__input"
              value={kategoriFilter}
              onChange={(event) => setKategoriFilter(event.target.value)}
            >
              <option value="">Semua</option>
              {KATEGORI_OPTIONS.map((option) => (
                <option key={option} value={option}>{option}</option>
              ))}
            </select>
          </div>

          <div className="filter__group">
            <label className="filter__label" htmlFor="jabatan">Jabatan</label>
            <select
              id="jabatan"
              name="jabatan"
              className="filter__input"
              value={jabatanFilter}
              onChange={(event) => setJabatanFilter(event.target.value)}
            >
              <option value="">Semua</option>
              {JABATAN_OPTIONS.map((option) => (
                <option key={option} value={option}>{option}</option>
              ))}
            </select>
          </div>

          <div className="filter__group">
            <label className="filter__label" htmlFor="satuan">Satuan</label>
            <select
              id="satuan"
              name="satuan"
              className="filter__input"
              value={satuanFilter}
              onChange={(event) => setSatuanFilter(event.target.value)}
            >
              <option value="">Semua</option>
              {SATUAN_OPTIONS.map((option) => (
                <option key={option} value={option}>{option}</option>
              ))}
            </select>
          </div>

          <div className="filter__group">
            <label className="filter__label" htmlFor="perPage">Data per halaman</label>
            <select
              id="perPage"
              name="perPage"
              className="filter__input"
              value={perPage}
              onChange={(event) => setPerPage(Number(event.target.value))}
            >
              {[5, 10, 15, 20].map((option) => (
                <option key={option} value={option}>{option}</option>
              ))}
            </select>
          </div>

          <button type="submit" className="button button--primary">
            Cari
          </button>
        </form>
      </section>

      <section className="admin__section admin__section--grid">
        <form className="form form--pane" onSubmit={handleSubmit}>
          <h2 className="form__title">{isEditing ? 'Ubah Komponen' : 'Tambah Komponen Baru'}</h2>

          {!isEditing && (
            <label className="form__label" htmlFor="id_komponen_gaji">
              ID Komponen
              <input
                id="id_komponen_gaji"
                name="id_komponen_gaji"
                className="form__input"
                type="number"
                min="1"
                value={formData.id_komponen_gaji}
                onChange={handleInputChange}
                required
              />
              {formErrors.id_komponen_gaji && <span className="form__error">{formErrors.id_komponen_gaji}</span>}
            </label>
          )}

          <label className="form__label" htmlFor="nama_komponen">
            Nama Komponen
            <input
              id="nama_komponen"
              name="nama_komponen"
              className="form__input"
              value={formData.nama_komponen}
              onChange={handleInputChange}
              required
            />
            {formErrors.nama_komponen && <span className="form__error">{formErrors.nama_komponen}</span>}
          </label>

          <label className="form__label" htmlFor="kategoriInput">
            Kategori
            <select
              id="kategoriInput"
              name="kategori"
              className="form__input"
              value={formData.kategori}
              onChange={handleInputChange}
              required
            >
              {KATEGORI_OPTIONS.map((option) => (
                <option key={option} value={option}>{option}</option>
              ))}
            </select>
            {formErrors.kategori && <span className="form__error">{formErrors.kategori}</span>}
          </label>

          <label className="form__label" htmlFor="jabatanInput">
            Jabatan
            <select
              id="jabatanInput"
              name="jabatan"
              className="form__input"
              value={formData.jabatan}
              onChange={handleInputChange}
              required
            >
              {JABATAN_OPTIONS.map((option) => (
                <option key={option} value={option}>{option}</option>
              ))}
            </select>
            {formErrors.jabatan && <span className="form__error">{formErrors.jabatan}</span>}
          </label>

          <label className="form__label" htmlFor="nominal">
            Nominal (IDR)
            <input
              id="nominal"
              name="nominal"
              className="form__input"
              type="number"
              min="0"
              step="0.01"
              value={formData.nominal}
              onChange={handleInputChange}
              required
            />
            {formErrors.nominal && <span className="form__error">{formErrors.nominal}</span>}
          </label>

          <label className="form__label" htmlFor="satuan">
            Satuan
            <select
              id="satuan"
              name="satuan"
              className="form__input"
              value={formData.satuan}
              onChange={handleInputChange}
              required
            >
              {SATUAN_OPTIONS.map((option) => (
                <option key={option} value={option}>{option}</option>
              ))}
            </select>
            {formErrors.satuan && <span className="form__error">{formErrors.satuan}</span>}
          </label>

          {error && <p className="form__error">{error}</p>}

          <div className="form__actions">
            <button type="submit" className="button button--primary">
              {isEditing ? 'Simpan Perubahan' : 'Tambah Komponen'}
            </button>
            {isEditing && (
              <button type="button" className="button button--ghost" onClick={resetForm}>
                Batalkan
              </button>
            )}
          </div>
        </form>

        <div className="table-wrapper">
          <div className="table-header">
            <h2 className="table-title">Daftar Komponen</h2>
            {loading && <span className="table-status">Memuat…</span>}
            {!loading && meta && (
              <span className="table-status">
                Menampilkan {items.length} dari total {meta.total} komponen
              </span>
            )}
          </div>

          <div className="table-scroll">
            <table className="table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Nama Komponen</th>
                  <th>Kategori</th>
                  <th>Jabatan</th>
                  <th>Nominal</th>
                  <th>Satuan</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                {items.length === 0 && (
                  <tr>
                    <td colSpan="7" className="table-empty">Belum ada data untuk ditampilkan.</td>
                  </tr>
                )}
                {items.map((row) => (
                  <tr key={row.id_komponen_gaji}>
                    <td>{row.id_komponen_gaji}</td>
                    <td>{row.nama_komponen}</td>
                    <td>{row.kategori}</td>
                    <td>{row.jabatan}</td>
                    <td>{formatCurrency(row.nominal)}</td>
                    <td>{row.satuan}</td>
                    <td>
                      <div className="table-actions">
                        <button type="button" className="button button--small" onClick={() => handleEdit(row)}>
                          Ubah
                        </button>
                        <button
                          type="button"
                          className="button button--small button--danger"
                          onClick={() => handleDelete(row.id_komponen_gaji)}
                        >
                          Hapus
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {meta && meta.last_page > 1 && (
            <nav className="pagination" aria-label="Navigasi halaman">
              <button
                type="button"
                className="button button--ghost"
                onClick={() => handlePageChange(meta.current_page - 1)}
                disabled={meta.current_page <= 1}
              >
                Sebelumnya
              </button>
              <span className="pagination__info">
                Halaman {meta.current_page} dari {meta.last_page}
              </span>
              <button
                type="button"
                className="button button--ghost"
                onClick={() => handlePageChange(meta.current_page + 1)}
                disabled={meta.current_page >= meta.last_page}
              >
                Selanjutnya
              </button>
            </nav>
          )}
        </div>
      </section>
    </div>
  );
}
