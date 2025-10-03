<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreKomponenGajiRequest;
use App\Http\Requests\Admin\UpdateKomponenGajiRequest;
use App\Models\KomponenGaji;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class KomponenGajiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min($perPage, 100));
        $search = trim((string) $request->query('search', ''));
        $kategori = $request->query('kategori');
        $jabatan = $request->query('jabatan');
        $satuan = $request->query('satuan');

        $query = KomponenGaji::query();

        if ($search !== '') {
            $normalized = mb_strtolower($search);
            $like = "%{$normalized}%";

            $query->where(function ($q) use ($normalized, $like, $search): void {
                $q->whereRaw('LOWER(nama_komponen) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(kategori) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(jabatan) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(satuan) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(CAST(nominal AS TEXT)) LIKE ?', [$like]);

                if (ctype_digit($search)) {
                    $q->orWhere('id_komponen_gaji', (int) $search);
                }
            });
        }

        if ($kategori && in_array($kategori, KomponenGaji::KATEGORI_VALUES, true)) {
            $query->where('kategori', $kategori);
        }

        if ($jabatan && in_array($jabatan, KomponenGaji::JABATAN_VALUES, true)) {
            $query->where('jabatan', $jabatan);
        }

        if ($satuan && in_array($satuan, KomponenGaji::SATUAN_VALUES, true)) {
            $query->where('satuan', $satuan);
        }

        $paginator = $query
            ->orderBy('id_komponen_gaji')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json($paginator);
    }

    public function store(StoreKomponenGajiRequest $request): JsonResponse
    {
        $data = $this->sanitizeData($request->validated());
        $komponen = KomponenGaji::create($data);

        return response()->json($komponen, 201);
    }

    public function show(int $id): JsonResponse
    {
        $komponen = KomponenGaji::find($id);

        if ($komponen === null) {
            return response()->json(['message' => 'Data komponen gaji tidak ditemukan.'], 404);
        }

        return response()->json($komponen);
    }

    public function update(UpdateKomponenGajiRequest $request, int $id): JsonResponse
    {
        $komponen = KomponenGaji::find($id);

        if ($komponen === null) {
            return response()->json(['message' => 'Data komponen gaji tidak ditemukan.'], 404);
        }

        $payload = $this->sanitizeData($request->validated());
        $komponen->update($payload);

        return response()->json($komponen);
    }

    public function destroy(int $id): Response|JsonResponse
    {
        $komponen = KomponenGaji::find($id);

        if ($komponen === null) {
            return response()->json(['message' => 'Data komponen gaji tidak ditemukan.'], 404);
        }

        DB::table('penggajian')->where('id_komponen_gaji', $id)->delete();
        $komponen->delete();

        return response()->noContent();
    }

    private function sanitizeData(array $data): array
    {
        if (array_key_exists('id_komponen_gaji', $data)) {
            $data['id_komponen_gaji'] = (int) $data['id_komponen_gaji'];
        }

        if (array_key_exists('nama_komponen', $data)) {
            $data['nama_komponen'] = trim($data['nama_komponen']);
        }

        foreach (['kategori', 'jabatan', 'satuan'] as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = trim((string) $data[$key]);
            }
        }

        if (array_key_exists('nominal', $data)) {
            $data['nominal'] = (float) $data['nominal'];
        }

        return $data;
    }
}
