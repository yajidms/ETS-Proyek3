<?php

namespace App\Services;

use App\Models\Anggota;
use App\Models\KomponenGaji;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PenggajianAggregator
{
    public function baseSummaryQuery(): Builder
    {
        $allowances = $this->resolveAllowances();

        return $this->buildAggregationQuery($allowances['spouse'], $allowances['child']);
    }

    public function detail(int $idAnggota): ?array
    {
        $allowances = $this->resolveAllowances();

        return $this->buildDetailPayload($idAnggota, $allowances['spouse'], $allowances['child']);
    }

    public function resolveAllowances(): array
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

    private function buildAggregationQuery(float $spouseAllowance, float $childAllowance): Builder
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

    private function formatDecimal(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
