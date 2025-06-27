<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController; // 既存のコントローラを参考にAPI用を作成
use App\Http\Controllers\ProjectController;
// ログインAPI
Route::post('/login', [AuthenticatedSessionController::class, 'storeApi']); // API用のログインメソッドを新規作成

// 認証が必要なルート
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    // API用のログインルート
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroyApi']); // API用のログアウトメソッド

    // ↓ここにアプリで使う他のAPIエンドポイントを追加していく
    Route::get('/projects', [ProjectController::class, 'indexApi']);
});
