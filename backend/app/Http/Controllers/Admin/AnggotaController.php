<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AnggotaController extends Controller
{
    public function index(): JsonResponse
    {
        $anggota = DB::table('anggota')
            ->select(
                'anggota.id_anggota',
                'anggota.nama_depan',
                'anggota.nama_belakang',
                'anggota.gelar_depan',
                'anggota.gelar_belakang',
                'anggota.jabatan',
                'anggota.status_pernikahan',
                DB::raw('COALESCE(SUM(komponen_gaji.nominal), 0) AS total_nominal')
            )
            ->leftJoin('penggajian', 'anggota.id_anggota', '=', 'penggajian.id_anggota')
            ->leftJoin('komponen_gaji', 'penggajian.id_komponen_gaji', '=', 'komponen_gaji.id_komponen_gaji')
            ->groupBy(
                'anggota.id_anggota',
                'anggota.nama_depan',
                'anggota.nama_belakang',
                'anggota.gelar_depan',
                'anggota.gelar_belakang',
                'anggota.jabatan',
                'anggota.status_pernikahan'
            )
            ->orderBy('anggota.id_anggota')
            ->get();

        return response()->json($anggota);
    }
}
