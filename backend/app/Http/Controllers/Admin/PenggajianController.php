<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePenggajianRequest;
use App\Models\Anggota;
use App\Models\KomponenGaji;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenggajianController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min($perPage, 100));
        $search = trim((string) $request->query('search', ''));

        $allowances = $this->resolveAllowances();
        $baseQuery = $this->buildAggregationQuery($allowances['spouse'], $allowances['child']);

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

        $allowances = $this->resolveAllowances();
        $payload = $this->buildDetailPayload($idAnggota, $allowances['spouse'], $allowances['child']);

        return response()->json($payload, 201);
    }

    public function show(int $idAnggota): JsonResponse
    {
        $allowances = $this->resolveAllowances();
        $payload = $this->buildDetailPayload($idAnggota, $allowances['spouse'], $allowances['child']);

        if ($payload === null) {
            return response()->json(['message' => 'Data anggota tidak ditemukan.'], 404);
        }

        return response()->json($payload);
    }

    private function buildAggregationQuery(float $spouseAllowance, float $childAllowance)
    {
        $spouseValue = $this->formatDecimal($spouseAllowance);
        $childValue = $this->formatDecimal($childAllowance);

        $baseSumExpression = "COALESCE(SUM(CASE WHEN komponen_gaji.satuan = 'Bulan' AND komponen_gaji.nama_komponen NOT IN ('Tunjangan Istri/Suami','Tunjangan Anak') THEN komponen_gaji.nominal ELSE 0 END), 0)";

        $limitedChildrenExpression = "CASE WHEN COALESCE(anggota.jumlah_anak, 0) > 2 THEN 2 ELSE COALESCE(anggota.jumlah_anak, 0) END";

        $takeHomeExpression = sprintf(
            "%s + CASE WHEN anggota.status_pernikahan = 'Kawin' THEN %s ELSE 0 END + ((%s) * %s)",
            $baseSumExpression,
            $spouseValue,
            $limitedChildrenExpression,
            $childValue
        );

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
                DB::raw($baseSumExpression . ' AS total_bulanan'),
                DB::raw($takeHomeExpression . ' AS take_home_pay'),
                DB::raw('COUNT(penggajian.id_komponen_gaji) AS jumlah_komponen')
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
    }

    private function buildDetailPayload(int $idAnggota, float $spouseAllowance, float $childAllowance): ?array
    {
        $summary = $this->buildAggregationQuery($spouseAllowance, $childAllowance)
            ->where('anggota.id_anggota', $idAnggota)
            ->first();

        if ($summary === null) {
            return null;
        }

        $components = DB::table('penggajian')
            ->join('komponen_gaji', 'penggajian.id_komponen_gaji', '=', 'komponen_gaji.id_komponen_gaji')
            ->where('penggajian.id_anggota', $idAnggota)
            ->select(
                'komponen_gaji.id_komponen_gaji',
                'komponen_gaji.nama_komponen',
                'komponen_gaji.kategori',
                'komponen_gaji.jabatan',
                'komponen_gaji.nominal',
                'komponen_gaji.satuan'
            )
            ->orderBy('komponen_gaji.kategori')
            ->orderBy('komponen_gaji.id_komponen_gaji')
            ->get();

        $spouseApplied = $summary->status_pernikahan === 'Kawin' ? $spouseAllowance : 0.0;
        $childrenApplied = min((int) ($summary->jumlah_anak ?? 0), 2) * $childAllowance;

        return [
            'anggota' => [
                'id_anggota' => (int) $summary->id_anggota,
                'nama_depan' => $summary->nama_depan,
                'nama_belakang' => $summary->nama_belakang,
                'gelar_depan' => $summary->gelar_depan,
                'gelar_belakang' => $summary->gelar_belakang,
                'jabatan' => $summary->jabatan,
                'status_pernikahan' => $summary->status_pernikahan,
                'jumlah_anak' => (int) ($summary->jumlah_anak ?? 0),
            ],
            'komponen_gaji' => $components,
            'summary' => [
                'jumlah_komponen' => (int) $summary->jumlah_komponen,
                'total_bulanan' => (float) $summary->total_bulanan,
                'tunjangan_pasangan' => (float) $spouseApplied,
                'tunjangan_anak' => (float) $childrenApplied,
                'take_home_pay' => (float) $summary->take_home_pay,
            ],
        ];
    }

    private function resolveAllowances(): array
    {
        $allowances = KomponenGaji::query()
            ->whereIn('nama_komponen', ['Tunjangan Istri/Suami', 'Tunjangan Anak'])
            ->get(['nama_komponen', 'nominal'])
            ->keyBy('nama_komponen');

        return [
            'spouse' => isset($allowances['Tunjangan Istri/Suami']) ? (float) $allowances['Tunjangan Istri/Suami']->nominal : 0.0,
            'child' => isset($allowances['Tunjangan Anak']) ? (float) $allowances['Tunjangan Anak']->nominal : 0.0,
        ];
    }

    private function formatDecimal(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
