<?php

namespace App\Http\Requests\Admin;

use App\Models\KomponenGaji;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKomponenGajiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = (int) $this->route('komponen_gaji');

        return [
            'nama_komponen' => ['sometimes', 'required', 'string', 'max:100'],
            'kategori' => ['sometimes', 'required', 'string', Rule::in(KomponenGaji::KATEGORI_VALUES)],
            'jabatan' => ['sometimes', 'required', 'string', Rule::in(KomponenGaji::JABATAN_VALUES)],
            'nominal' => ['sometimes', 'required', 'numeric', 'min:0'],
            'satuan' => ['sometimes', 'required', 'string', Rule::in(KomponenGaji::SATUAN_VALUES)],
            'id_komponen_gaji' => ['prohibited'],
        ];
    }
}
