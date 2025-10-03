<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penggajian', function (Blueprint $table): void {
            $table->index('id_anggota', 'penggajian_id_anggota_index');
            $table->index('id_komponen_gaji', 'penggajian_id_komponen_gaji_index');
        });
    }

    public function down(): void
    {
        Schema::table('penggajian', function (Blueprint $table): void {
            $table->dropIndex('penggajian_id_anggota_index');
            $table->dropIndex('penggajian_id_komponen_gaji_index');
        });
    }
};
