<?php

namespace App\Http\Requests\Admin;

use App\Models\KomponenGaji;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKomponenGajiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_komponen_gaji' => ['required', 'integer', 'min:1', 'max:999999999999', 'unique:komponen_gaji,id_komponen_gaji'],
            'nama_komponen' => ['required', 'string', 'max:100'],
            'kategori' => ['required', 'string', Rule::in(KomponenGaji::KATEGORI_VALUES)],
            'jabatan' => ['required', 'string', Rule::in(KomponenGaji::JABATAN_VALUES)],
            'nominal' => ['required', 'numeric', 'min:0'],
            'satuan' => ['required', 'string', Rule::in(KomponenGaji::SATUAN_VALUES)],
        ];
    }
}
