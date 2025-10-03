<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KomponenGaji extends Model
{
    use HasFactory;

    public const KATEGORI_VALUES = ['Gaji Pokok', 'Tunjangan Melekat', 'Tunjangan Lain'];
    public const JABATAN_VALUES = ['Ketua', 'Wakil Ketua', 'Anggota', 'Semua'];
    public const SATUAN_VALUES = ['Bulan', 'Hari', 'Periode'];

    protected $table = 'komponen_gaji';

    protected $primaryKey = 'id_komponen_gaji';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'id_komponen_gaji',
        'nama_komponen',
        'kategori',
        'jabatan',
        'nominal',
        'satuan',
    ];

    protected $casts = [
        'id_komponen_gaji' => 'integer',
        'nominal' => 'float',
    ];
}
