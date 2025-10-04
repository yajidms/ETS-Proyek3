<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePenggajianRequest;
use App\Models\Anggota;
use App\Models\KomponenGaji;
use App\Services\PenggajianAggregator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenggajianController extends Controller
{
    public function __construct(private readonly PenggajianAggregator $aggregator)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min($perPage, 100));
        $search = trim((string) $request->query('search', ''));

        $baseQuery = $this->aggregator->baseSummaryQuery();

        $subQuery = DB::query()->fromSub($baseQuery, 'penggajian_ringkasan')->select('*');

        if ($search !== '') {
            $normalized = mb_strtolower($search);
            $like = "%{$normalized}%";

            $subQuery->where(function ($query) use ($like): void {
                $query->whereRaw('LOWER(nama_depan) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(nama_belakang) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(gelar_depan) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(gelar_belakang) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(jabatan) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(status_pernikahan) LIKE ?', [$like])
                    ->orWhereRaw('CAST(id_anggota AS TEXT) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(CAST(take_home_pay AS TEXT)) LIKE ?', [$like]);
            });
        }

        $paginator = $subQuery
            ->orderBy('id_anggota')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json($paginator);
    }

    public function store(StorePenggajianRequest $request): JsonResponse
    {
        $data = $request->validated();
        $idAnggota = (int) $data['id_anggota'];
        $komponenIds = array_values(array_unique(array_map('intval', $data['komponen_gaji_ids'])));

        $anggota = Anggota::find($idAnggota);
        if ($anggota === null) {
            return response()->json(['message' => 'Data anggota tidak ditemukan.'], 404);
        }

        $komponen = KomponenGaji::query()
            ->whereIn('id_komponen_gaji', $komponenIds)
            ->get()
            ->keyBy('id_komponen_gaji');

        if (count($komponenIds) !== $komponen->count()) {
            $missing = array_values(array_diff($komponenIds, $komponen->keys()->all()));

            return response()->json([
                'message' => 'Beberapa komponen gaji tidak ditemukan.',
                'missing_components' => $missing,
                'errors' => ['komponen_gaji_ids' => 'Beberapa komponen gaji tidak ditemukan.'],
            ], 422);
        }

        foreach ($komponen as $item) {
            if ($item->jabatan !== 'Semua' && $item->jabatan !== $anggota->jabatan) {
                return response()->json([
                    'message' => sprintf(
                        'Komponen gaji "%s" tidak dapat diberikan ke jabatan %s.',
                        $item->nama_komponen,
                        $anggota->jabatan
                    ),
                    'errors' => ['komponen_gaji_ids' => 'Komponen gaji yang dipilih tidak sesuai jabatan anggota.'],
                ], 422);
            }
        }

        $existing = DB::table('penggajian')
            ->where('id_anggota', $idAnggota)
            ->whereIn('id_komponen_gaji', $komponenIds)
            ->pluck('id_komponen_gaji')
            ->all();

        if (! empty($existing)) {
            return response()->json([
                'message' => 'Terdapat komponen gaji yang sudah terdaftar untuk anggota ini.',
                'duplicate_components' => array_map('intval', $existing),
                'errors' => ['komponen_gaji_ids' => 'Beberapa komponen sudah terdaftar sebelumnya.'],
            ], 422);
        }

        DB::transaction(function () use ($idAnggota, $komponenIds): void {
            $rows = array_map(static function (int $idKomponen) use ($idAnggota): array {
                return [
                    'id_anggota' => $idAnggota,
                    'id_komponen_gaji' => $idKomponen,
                ];
            }, $komponenIds);

            DB::table('penggajian')->insert($rows);
        });

        $payload = $this->aggregator->detail($idAnggota);

        return response()->json($payload, 201);
    }

    public function show(int $idAnggota): JsonResponse
    {
        $payload = $this->aggregator->detail($idAnggota);

        if ($payload === null) {
            return response()->json(['message' => 'Data anggota tidak ditemukan.'], 404);
        }

        return response()->json($payload);
    }
}
