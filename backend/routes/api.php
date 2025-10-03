<?php

use App\Http\Controllers\Admin\AnggotaController;
use App\Http\Controllers\Admin\KomponenGajiController;
use App\Http\Controllers\Admin\PenggajianController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['jwt'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/me', function (Request $request) {
        $user = $request->attributes->get('auth_user');

        return response()->json([
            'id' => $user->id_pengguna,
            'username' => $user->username,
            'role' => $user->role,
            'nama_depan' => $user->nama_depan,
            'nama_belakang' => $user->nama_belakang,
        ]);
    });

    Route::middleware(['role:Admin'])->prefix('admin')->group(function () {
        Route::apiResource('anggota', AnggotaController::class);
        Route::apiResource('komponen-gaji', KomponenGajiController::class);
        Route::get('penggajian', [PenggajianController::class, 'index']);
        Route::post('penggajian', [PenggajianController::class, 'store']);
        Route::get('penggajian/{id}', [PenggajianController::class, 'show']);
    });
});
