'use client';

import { useEffect, useMemo, useState } from 'react';
import { useRouter } from 'next/navigation';

const TOKEN_KEY = 'jwt_token';
const JABATAN_OPTIONS = ['Ketua', 'Wakil Ketua', 'Anggota'];
const STATUS_PERNIKAHAN_OPTIONS = ['Kawin', 'Belum Kawin', 'Cerai Hidup', 'Cerai Mati'];
const DEFAULT_PER_PAGE = 10;

const emptyForm = {
  id_anggota: '',
  nama_depan: '',
  nama_belakang: '',
  gelar_depan: '',
  gelar_belakang: '',
  jabatan: 'Anggota',
  status_pernikahan: 'Kawin',
  jumlah_anak: 0,
};

function formatCurrency(value) {
  const number = Number(value ?? 0);
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(number);
}

export default function AnggotaPage() {
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
  const [jabatanFilter, setJabatanFilter] = useState('');
  const [perPage, setPerPage] = useState(DEFAULT_PER_PAGE);

  const authHeaders = useMemo(() => {
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
  }, [tokenChecked, perPage, jabatanFilter]);

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
      if (jabatanFilter) {
        params.set('jabatan', jabatanFilter);
      }

      const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/api/admin/anggota?${params.toString()}`, {
        headers: authHeaders,
      });

      if (!response.ok) {
        throw new Error(`Gagal memuat data anggota (${response.status})`);
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

    if (name === 'jumlah_anak') {
      setFormData((prev) => ({
        ...prev,
        jumlah_anak: value === '' ? '' : Number(value),
      }));
      return;
    }

    setFormData((prev) => ({
      ...prev,
      [name]: value,
    }));
  }

  function resetForm() {
    setFormData(emptyForm);
    setIsEditing(false);
    setFormErrors({});
  }

  function validateForm(clientData, validateId = true) {
    const { id_anggota, jumlah_anak, nama_depan, nama_belakang } = clientData;
    const errors = {};

    if (validateId && (!id_anggota || Number(id_anggota) <= 0)) {
      errors.id_anggota = 'ID anggota wajib diisi dan harus lebih dari 0.';
    }

    if (!nama_depan.trim()) {
      errors.nama_depan = 'Nama depan wajib diisi.';
    }

    if (!nama_belakang.trim()) {
      errors.nama_belakang = 'Nama belakang wajib diisi.';
    }

    if (jumlah_anak === '' || Number.isNaN(Number(jumlah_anak)) || Number(jumlah_anak) < 0) {
      errors.jumlah_anak = 'Jumlah anak wajib diisi dan tidak boleh negatif.';
    }

    return errors;
  }

  async function handleSubmit(event) {
    event.preventDefault();
    setError('');
    setFormErrors({});

    const validateId = !isEditing;
    const errors = validateForm(formData, validateId);
    if (Object.keys(errors).length > 0) {
      setFormErrors(errors);
      return;
    }

    const payload = {
      nama_depan: formData.nama_depan.trim(),
      nama_belakang: formData.nama_belakang.trim(),
      gelar_depan: formData.gelar_depan.trim() || null,
      gelar_belakang: formData.gelar_belakang.trim() || null,
      jabatan: formData.jabatan,
      status_pernikahan: formData.status_pernikahan,
  jumlah_anak: Number(formData.jumlah_anak),
    };

    let url = `${process.env.NEXT_PUBLIC_API_URL}/api/admin/anggota`;
    let method = 'POST';

    if (isEditing) {
      url = `${url}/${formData.id_anggota}`;
      method = 'PUT';
    } else {
      payload.id_anggota = Number(formData.id_anggota);
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

      await loadData(isEditing && meta ? meta.current_page : 1);
      resetForm();
    } catch (err) {
      setError(err.message ?? 'Terjadi kesalahan saat menyimpan data');
    }
  }

  function handleEdit(row) {
    setFormData({
      id_anggota: row.id_anggota,
      nama_depan: row.nama_depan ?? '',
      nama_belakang: row.nama_belakang ?? '',
      gelar_depan: row.gelar_depan ?? '',
      gelar_belakang: row.gelar_belakang ?? '',
      jabatan: row.jabatan ?? 'Anggota',
      status_pernikahan: row.status_pernikahan ?? 'Kawin',
      jumlah_anak: row.jumlah_anak ?? 0,
    });
    setIsEditing(true);
    setFormErrors({});
  }

  async function handleDelete(id) {
    if (!window.confirm('Hapus data anggota ini?')) {
      return;
    }

    try {
      const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/api/admin/anggota/${id}`, {
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
          <h1 className="admin__title">Manajemen Anggota DPR</h1>
          <p className="admin__subtitle">Tambah, ubah, cari, dan hapus data anggota secara real-time.</p>
        </div>
        <button
          type="button"
          className="button button--secondary"
          onClick={() => {
            localStorage.removeItem(TOKEN_KEY);
            localStorage.removeItem(`${TOKEN_KEY}_role`);
            localStorage.removeItem(`${TOKEN_KEY}_exp`);
            router.replace('/login');
          }}
        >
          Keluar
        </button>
      </header>

      <section className="admin__section">
        <form className="filter" onSubmit={handleSearch}>
          <div className="filter__group">
            <label className="filter__label" htmlFor="search">Pencarian</label>
            <input
              id="search"
              name="search"
              className="filter__input"
              placeholder="Cari nama, jabatan, atau ID anggota"
              value={searchTerm}
              onChange={(event) => setSearchTerm(event.target.value)}
            />
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
          <h2 className="form__title">{isEditing ? 'Ubah Data Anggota' : 'Tambah Anggota Baru'}</h2>

          {!isEditing && (
            <label className="form__label" htmlFor="id_anggota">
              ID Anggota
              <input
                id="id_anggota"
                name="id_anggota"
                className="form__input"
                type="number"
                min="1"
                value={formData.id_anggota}
                onChange={handleInputChange}
                required
              />
              {formErrors.id_anggota && <span className="form__error">{formErrors.id_anggota}</span>}
            </label>
          )}

          <label className="form__label" htmlFor="nama_depan">
            Nama Depan
            <input
              id="nama_depan"
              name="nama_depan"
              className="form__input"
              value={formData.nama_depan}
              onChange={handleInputChange}
              required
            />
            {formErrors.nama_depan && <span className="form__error">{formErrors.nama_depan}</span>}
          </label>

          <label className="form__label" htmlFor="nama_belakang">
            Nama Belakang
            <input
              id="nama_belakang"
              name="nama_belakang"
              className="form__input"
              value={formData.nama_belakang}
              onChange={handleInputChange}
              required
            />
            {formErrors.nama_belakang && <span className="form__error">{formErrors.nama_belakang}</span>}
          </label>

          <label className="form__label" htmlFor="gelar_depan">
            Gelar Depan
            <input
              id="gelar_depan"
              name="gelar_depan"
              className="form__input"
              value={formData.gelar_depan}
              onChange={handleInputChange}
            />
          </label>

          <label className="form__label" htmlFor="gelar_belakang">
            Gelar Belakang
            <input
              id="gelar_belakang"
              name="gelar_belakang"
              className="form__input"
              value={formData.gelar_belakang}
              onChange={handleInputChange}
            />
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

          <label className="form__label" htmlFor="status_pernikahan">
            Status Pernikahan
            <select
              id="status_pernikahan"
              name="status_pernikahan"
              className="form__input"
              value={formData.status_pernikahan}
              onChange={handleInputChange}
              required
            >
              {STATUS_PERNIKAHAN_OPTIONS.map((option) => (
                <option key={option} value={option}>{option}</option>
              ))}
            </select>
            {formErrors.status_pernikahan && <span className="form__error">{formErrors.status_pernikahan}</span>}
          </label>

          <label className="form__label" htmlFor="jumlah_anak">
            Jumlah Anak
            <input
              id="jumlah_anak"
              name="jumlah_anak"
              className="form__input"
              type="number"
              min="0"
              value={formData.jumlah_anak}
              onChange={handleInputChange}
              required
            />
            {formErrors.jumlah_anak && <span className="form__error">{formErrors.jumlah_anak}</span>}
          </label>

          {error && <p className="form__error">{error}</p>}

          <div className="form__actions">
            <button type="submit" className="button button--primary">
              {isEditing ? 'Simpan Perubahan' : 'Tambah Anggota'}
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
            <h2 className="table-title">Daftar Anggota</h2>
            {loading && <span className="table-status">Memuat…</span>}
            {!loading && meta && (
              <span className="table-status">
                Menampilkan {items.length} dari total {meta.total} anggota
              </span>
            )}
          </div>

          <div className="table-scroll">
            <table className="table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Nama</th>
                  <th>Jabatan</th>
                  <th>Status</th>
                  <th>Jumlah Anak</th>
                  <th>Total Kompensasi</th>
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
                  <tr key={row.id_anggota}>
                    <td>{row.id_anggota}</td>
                    <td>
                      <div className="table-name">
                        <span className="table-name__primary">{`${row.nama_depan} ${row.nama_belakang}`}</span>
                        {(row.gelar_depan || row.gelar_belakang) && (
                          <span className="table-name__secondary">{`${row.gelar_depan ?? ''} ${row.gelar_belakang ?? ''}`.trim()}</span>
                        )}
                      </div>
                    </td>
                    <td>{row.jabatan}</td>
                    <td>{row.status_pernikahan}</td>
                    <td>{row.jumlah_anak}</td>
                    <td>{formatCurrency(row.total_nominal)}</td>
                    <td>
                      <div className="table-actions">
                        <button type="button" className="button button--small" onClick={() => handleEdit(row)}>
                          Ubah
                        </button>
                        <button
                          type="button"
                          className="button button--small button--danger"
                          onClick={() => handleDelete(row.id_anggota)}
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
