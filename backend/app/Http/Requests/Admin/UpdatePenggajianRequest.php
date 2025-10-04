<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePenggajianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('komponen_gaji_ids') && is_string($this->komponen_gaji_ids)) {
            $decoded = json_decode($this->komponen_gaji_ids, true);
            if (is_array($decoded)) {
                $this->merge(['komponen_gaji_ids' => $decoded]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'komponen_gaji_ids' => ['present', 'array'],
            'komponen_gaji_ids.*' => ['required', 'integer', 'distinct', 'min:1', 'exists:komponen_gaji,id_komponen_gaji'],
        ];
    }

    public function messages(): array
    {
        return [
            'komponen_gaji_ids.present' => 'Daftar komponen gaji wajib disertakan meski kosong.',
            'komponen_gaji_ids.array' => 'Format komponen gaji tidak valid.',
            'komponen_gaji_ids.*.distinct' => 'Terdapat komponen gaji yang dipilih lebih dari satu kali.',
        ];
    }
}
