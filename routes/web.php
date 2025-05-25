<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\GanttChartController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MeasurementController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\CostController;

// ホーム
Route::get('/', [HomeController::class, 'index'])->name('home.index');

Route::get('/dashboard', fn() => redirect()->route('home.index'))->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    // 衣装案件
    Route::resource('projects', ProjectController::class);
    // 担当者更新用のルート
    Route::post('/projects/{project}/tasks/{task}/assignee', [TaskController::class, 'updateAssignee'])->name('projects.tasks.assignee');


    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 工程
    Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
    // Route::get('tasks/export', [TaskController::class, 'export'])->name('tasks.export'); // exportメソッドが見当たらないためコメントアウト
    Route::resource('projects.tasks', TaskController::class)->except(['index']);
    Route::post('projects/{project}/tasks/{task}/progress', [TaskController::class, 'updateProgress'])->name('tasks.update_progress');
    Route::post('tasks/update-position', [TaskController::class, 'updatePosition'])->name('tasks.update_position');
    Route::post('tasks/update-parent', [TaskController::class, 'updateParent'])->name('tasks.update_parent');

    Route::post('/projects/{project}/tasks/{task}/assignee', [TaskController::class, 'updateAssignee'])->name('tasks.assignee');

    // ファイル関連のルート
    Route::post('/projects/{project}/tasks/{task}/files', [TaskController::class, 'uploadFiles'])->name('projects.tasks.files.upload');
    Route::get('/projects/{project}/tasks/{task}/files', [TaskController::class, 'getFiles'])->name('projects.tasks.files.index');
    Route::get('/projects/{project}/tasks/{task}/files/{file}/download', [TaskController::class, 'downloadFile'])->name('projects.tasks.files.download');
    Route::delete('/projects/{project}/tasks/{task}/files/{file}', [TaskController::class, 'deleteFile'])->name('projects.tasks.files.destroy');

    // ガントチャート
    Route::get('gantt', [GanttChartController::class, 'index'])->name('gantt.index');

    // カレンダー
    Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
    // Breezeが生成したプロファイルルート
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [\App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');

    // 採寸データ (案件詳細ページ内で処理)
    Route::post('/projects/{project}/measurements', [MeasurementController::class, 'store'])->name('projects.measurements.store');
    Route::delete('/projects/{project}/measurements/{measurement}', [MeasurementController::class, 'destroy'])->name('projects.measurements.destroy');

    // 材料データ (案件詳細ページ内で処理)
    Route::post('/projects/{project}/materials', [MaterialController::class, 'store'])->name('projects.materials.store');
    Route::patch('/projects/{project}/materials/{material}', [MaterialController::class, 'update'])->name('projects.materials.update');
    Route::delete('/projects/{project}/materials/{material}', [MaterialController::class, 'destroy'])->name('projects.materials.destroy');

    // コストデータ (案件詳細ページ内で処理)
    Route::post('/projects/{project}/costs', [CostController::class, 'store'])->name('projects.costs.store');
    Route::delete('/projects/{project}/costs/{cost}', [CostController::class, 'destroy'])->name('projects.costs.destroy');

    // 管理機能
    Route::get('/users', [\App\Http\Controllers\UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/edit', [\App\Http\Controllers\UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [\App\Http\Controllers\UserController::class, 'update'])->name('users.update');
    Route::get('/roles', [\App\Http\Controllers\RolePermissionController::class, 'index'])->name('roles.index');
    Route::put('/roles/{role}', [\App\Http\Controllers\RolePermissionController::class, 'update'])->name('roles.update');
});

require __DIR__ . '/auth.php';
