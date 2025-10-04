<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePenggajianRequest;
use App\Http\Requests\Admin\UpdatePenggajianRequest;
use App\Models\Anggota;
use App\Models\KomponenGaji;
use App\Services\PenggajianAggregator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

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

        $komponen = $this->validateKomponenUntukAnggota($anggota, $komponenIds);
        if ($komponen instanceof JsonResponse) {
            return $komponen;
        }

        $existing = DB::table('penggajian')
            ->where('id_anggota', $idAnggota)
            ->whereIn('id_komponen_gaji', $komponenIds)
            ->pluck('id_komponen_gaji')
            ->map(static fn ($id) => (int) $id)
            ->all();

        if (! empty($existing)) {
            return response()->json([
                'message' => 'Terdapat komponen gaji yang sudah terdaftar untuk anggota ini.',
                'duplicate_components' => $existing,
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

        $payload = $this->aggregator->detail($idAnggota) ?? $this->fallbackDetailPayload($anggota);

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

    public function update(UpdatePenggajianRequest $request, int $id): JsonResponse
    {
        $idAnggota = $id;
        $anggota = Anggota::find($idAnggota);
        if ($anggota === null) {
            return response()->json(['message' => 'Data anggota tidak ditemukan.'], 404);
        }

        $data = $request->validated();
        $komponenIds = array_values(array_unique(array_map('intval', $data['komponen_gaji_ids'])));

        $komponen = $this->validateKomponenUntukAnggota($anggota, $komponenIds);
        if ($komponen instanceof JsonResponse) {
            return $komponen;
        }

        DB::transaction(function () use ($idAnggota, $komponenIds): void {
            if ($komponenIds === []) {
                DB::table('penggajian')->where('id_anggota', $idAnggota)->delete();

                return;
            }

            DB::table('penggajian')
                ->where('id_anggota', $idAnggota)
                ->whereNotIn('id_komponen_gaji', $komponenIds)
                ->delete();

            $existing = DB::table('penggajian')
                ->where('id_anggota', $idAnggota)
                ->whereIn('id_komponen_gaji', $komponenIds)
                ->pluck('id_komponen_gaji')
                ->map(static fn ($id) => (int) $id)
                ->all();

            $toInsert = array_values(array_diff($komponenIds, $existing));

            if (! empty($toInsert)) {
                $rows = array_map(static function (int $idKomponen) use ($idAnggota): array {
                    return [
                        'id_anggota' => $idAnggota,
                        'id_komponen_gaji' => $idKomponen,
                    ];
                }, $toInsert);

                DB::table('penggajian')->insert($rows);
            }
        });

        $payload = $this->aggregator->detail($idAnggota) ?? $this->fallbackDetailPayload($anggota);

        return response()->json($payload);
    }

    public function destroy(int $id): Response|JsonResponse
    {
        $idAnggota = $id;
        $anggota = Anggota::find($idAnggota);
        if ($anggota === null) {
            return response()->json(['message' => 'Data anggota tidak ditemukan.'], 404);
        }

        DB::table('penggajian')->where('id_anggota', $idAnggota)->delete();

        return response()->noContent();
    }

    public function destroyKomponen(int $id, int $idKomponen): JsonResponse
    {
        $idAnggota = $id;
        $anggota = Anggota::find($idAnggota);
        if ($anggota === null) {
            return response()->json(['message' => 'Data anggota tidak ditemukan.'], 404);
        }

        $exists = DB::table('penggajian')
            ->where('id_anggota', $idAnggota)
            ->where('id_komponen_gaji', $idKomponen)
            ->exists();

        if (! $exists) {
            return response()->json(['message' => 'Relasi komponen gaji untuk anggota ini tidak ditemukan.'], 404);
        }

        DB::table('penggajian')
            ->where('id_anggota', $idAnggota)
            ->where('id_komponen_gaji', $idKomponen)
            ->delete();

        $payload = $this->aggregator->detail($idAnggota) ?? $this->fallbackDetailPayload($anggota);

        return response()->json($payload);
    }

    private function validateKomponenUntukAnggota(Anggota $anggota, array $komponenIds): Collection|JsonResponse
    {
        if ($komponenIds === []) {
            return collect();
        }

        $komponen = KomponenGaji::query()
            ->whereIn('id_komponen_gaji', $komponenIds)
            ->get()
            ->keyBy('id_komponen_gaji');

        if (count($komponenIds) !== $komponen->count()) {
            $missing = array_values(array_diff($komponenIds, $komponen->keys()->map(static fn ($id) => (int) $id)->all()));

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

        return $komponen;
    }

    private function fallbackDetailPayload(Anggota $anggota): array
    {
        $allowances = $this->aggregator->resolveAllowances();
        $spouseAllowance = $anggota->status_pernikahan === 'Kawin' ? $allowances['spouse'] : 0.0;
        $childAllowance = min((int) ($anggota->jumlah_anak ?? 0), 2) * $allowances['child'];

        return [
            'anggota' => [
                'id_anggota' => (int) $anggota->id_anggota,
                'nama_depan' => $anggota->nama_depan,
                'nama_belakang' => $anggota->nama_belakang,
                'gelar_depan' => $anggota->gelar_depan,
                'gelar_belakang' => $anggota->gelar_belakang,
                'jabatan' => $anggota->jabatan,
                'status_pernikahan' => $anggota->status_pernikahan,
                'jumlah_anak' => (int) ($anggota->jumlah_anak ?? 0),
            ],
            'komponen_gaji' => [],
            'summary' => [
                'jumlah_komponen' => 0,
                'total_bulanan' => 0.0,
                'tunjangan_pasangan' => (float) $spouseAllowance,
                'tunjangan_anak' => (float) $childAllowance,
                'take_home_pay' => (float) ($spouseAllowance + $childAllowance),
            ],
        ];
    }
}
