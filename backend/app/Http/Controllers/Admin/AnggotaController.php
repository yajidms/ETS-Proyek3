<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAnggotaRequest;
use App\Http\Requests\Admin\UpdateAnggotaRequest;
use App\Models\Anggota;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class AnggotaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min($perPage, 100));
        $search = trim((string) $request->query('search', ''));
        $jabatanFilter = $request->query('jabatan');

        $query = Anggota::query()
            ->leftJoin('penggajian', 'anggota.id_anggota', '=', 'penggajian.id_anggota')
            ->leftJoin('komponen_gaji', 'penggajian.id_komponen_gaji', '=', 'komponen_gaji.id_komponen_gaji')
            ->select(
                'anggota.id_anggota',
                'anggota.nama_depan',
                'anggota.nama_belakang',
                'anggota.gelar_depan',
                'anggota.gelar_belakang',
                'anggota.jabatan',
                'anggota.status_pernikahan',
                'anggota.jumlah_anak',
                DB::raw('COALESCE(SUM(komponen_gaji.nominal), 0) AS total_nominal')
            )
            ->groupBy(
                'anggota.id_anggota',
                'anggota.nama_depan',
                'anggota.nama_belakang',
                'anggota.gelar_depan',
                'anggota.gelar_belakang',
                'anggota.jabatan',
                'anggota.status_pernikahan',
                'anggota.jumlah_anak'
            );

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('anggota.nama_depan', 'ILIKE', "%{$search}%")
                    ->orWhere('anggota.nama_belakang', 'ILIKE', "%{$search}%")
                    ->orWhere('anggota.jabatan', 'ILIKE', "%{$search}%");

                if (ctype_digit($search)) {
                    $q->orWhere('anggota.id_anggota', (int) $search);
                }
            });
        }

        if ($jabatanFilter && in_array($jabatanFilter, ['Ketua', 'Wakil Ketua', 'Anggota'], true)) {
            $query->where('anggota.jabatan', $jabatanFilter);
        }

        $paginator = $query
            ->orderBy('anggota.id_anggota')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json($paginator);
    }

    public function store(StoreAnggotaRequest $request): JsonResponse
    {
        $anggota = Anggota::create($request->validated());
        $payload = $this->findAnggotaWithAggregates($anggota->id_anggota);

        return response()->json($payload, 201);
    }

    public function show(int $id): JsonResponse
    {
        $payload = $this->findAnggotaWithAggregates($id);

        if ($payload === null) {
            return response()->json(['message' => 'Data anggota tidak ditemukan.'], 404);
        }

        return response()->json($payload);
    }

    public function update(UpdateAnggotaRequest $request, int $id): JsonResponse
    {
        $anggota = Anggota::find($id);

        if ($anggota === null) {
            return response()->json(['message' => 'Data anggota tidak ditemukan.'], 404);
        }

        $anggota->update($request->validated());

        $payload = $this->findAnggotaWithAggregates($id);

        return response()->json($payload);
    }

    public function destroy(int $id): Response|JsonResponse
    {
        $anggota = Anggota::find($id);

        if ($anggota === null) {
            return response()->json(['message' => 'Data anggota tidak ditemukan.'], 404);
        }

        DB::table('penggajian')->where('id_anggota', $id)->delete();
        $anggota->delete();

        return response()->noContent();
    }

    private function findAnggotaWithAggregates(int $id): ?object
    {
        return Anggota::query()
            ->leftJoin('penggajian', 'anggota.id_anggota', '=', 'penggajian.id_anggota')
            ->leftJoin('komponen_gaji', 'penggajian.id_komponen_gaji', '=', 'komponen_gaji.id_komponen_gaji')
            ->select(
                'anggota.id_anggota',
                'anggota.nama_depan',
                'anggota.nama_belakang',
                'anggota.gelar_depan',
                'anggota.gelar_belakang',
                'anggota.jabatan',
                'anggota.status_pernikahan',
                'anggota.jumlah_anak',
                DB::raw('COALESCE(SUM(komponen_gaji.nominal), 0) AS total_nominal')
            )
            ->where('anggota.id_anggota', $id)
            ->groupBy(
                'anggota.id_anggota',
                'anggota.nama_depan',
                'anggota.nama_belakang',
                'anggota.gelar_depan',
                'anggota.gelar_belakang',
                'anggota.jabatan',
                'anggota.status_pernikahan',
                'anggota.jumlah_anak'
            )
            ->first();
    }
}
