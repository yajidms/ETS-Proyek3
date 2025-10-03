<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePenggajianRequest extends FormRequest
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
            'id_anggota' => ['required', 'integer', 'min:1', 'exists:anggota,id_anggota'],
            'komponen_gaji_ids' => ['required', 'array', 'min:1'],
            'komponen_gaji_ids.*' => ['required', 'integer', 'distinct', 'min:1', 'exists:komponen_gaji,id_komponen_gaji'],
        ];
    }

    public function messages(): array
    {
        return [
            'komponen_gaji_ids.required' => 'Minimal pilih satu komponen gaji.',
            'komponen_gaji_ids.array' => 'Format komponen gaji tidak valid.',
            'komponen_gaji_ids.*.distinct' => 'Terdapat komponen gaji yang dipilih lebih dari satu kali.',
        ];
    }
}
