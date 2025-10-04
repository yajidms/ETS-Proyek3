<?php

namespace App\Http\Controllers;

use App\Services\PenggajianAggregator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicController extends Controller
{
    public function __construct(private readonly PenggajianAggregator $aggregator)
    {
    }

    public function daftarAnggota(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min($perPage, 200));
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

    public function dataPenggajian(int $idAnggota): JsonResponse
    {
        $payload = $this->aggregator->detail($idAnggota);

        if ($payload === null) {
            return response()->json(['message' => 'Data anggota tidak ditemukan.'], 404);
        }

        return response()->json($payload);
    }
}
