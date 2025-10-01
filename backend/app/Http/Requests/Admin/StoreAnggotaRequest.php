<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnggotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_anggota' => ['required', 'integer', 'min:1', 'unique:anggota,id_anggota'],
            'nama_depan' => ['required', 'string', 'max:100'],
            'nama_belakang' => ['required', 'string', 'max:100'],
            'gelar_depan' => ['nullable', 'string', 'max:50'],
            'gelar_belakang' => ['nullable', 'string', 'max:50'],
            'jabatan' => ['required', 'in:Ketua,Wakil Ketua,Anggota'],
            'status_pernikahan' => ['required', 'in:Kawin,Belum Kawin,Cerai Hidup,Cerai Mati'],
            'jumlah_anak' => ['required', 'integer', 'min:0'],
        ];
    }
}
