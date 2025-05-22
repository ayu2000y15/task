<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\GanttChartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CalendarController;

use App\Http\Controllers\Admin\AdminContentSchemaController;
use App\Http\Controllers\Admin\AdminContentDataController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdminPhotoController;
use App\Http\Controllers\Admin\AdminDefinitionController;
use App\Http\Controllers\Admin\AdminHpTextController;


// ホーム
Route::get('/', [HomeController::class, 'index'])->name('home.index');

// プロジェクト
Route::resource('projects', ProjectController::class);
// 担当者更新用のルート
Route::post('/projects/{project}/tasks/{task}/assignee', [TaskController::class, 'updateAssignee'])->name('projects.tasks.assignee');


// タスク
Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
Route::get('tasks/export', [TaskController::class, 'export'])->name('tasks.export');
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



// 管理者ページログイン
Route::get('/login', [AdminController::class, 'login'])->name('login');
Route::post('/login/access', [AdminController::class, 'loginAccess'])->name('login.access');
// 管理者ページログアウト
Route::get('/logout', [AdminController::class, 'logout'])->name('logout');

// 管理者ページ
Route::get('/admin', [DashboardController::class, 'index'])->name('admin.dashboard');

//画像管理
Route::get('/admin/photo', [AdminPhotoController::class, 'index'])->name('admin.photo');
Route::post('/admin/photo/store', [AdminPhotoController::class, 'store'])->name('admin.photo.store');
Route::get('/admin/photo/edit/{image_id}', [AdminPhotoController::class, 'edit'])->name('admin.photo.edit');
Route::put('/admin/photo/update', [AdminPhotoController::class, 'update'])->name('admin.photo.update');
Route::delete('/admin/photo/delete', [AdminPhotoController::class, 'delete'])->name('admin.photo.delete');
Route::get('/admin/photo/delete-image/{image_id}', [AdminPhotoController::class, 'deleteImage'])->name('admin.photo.delete-image');

//汎用テーブル管理
Route::get('/admin/definition', [AdminDefinitionController::class, 'index'])->name('admin.definition');
Route::post('/admin/definition/store', [AdminDefinitionController::class, 'store'])->name('admin.definition.store');
Route::post('/admin/definition/update', [AdminDefinitionController::class, 'update'])->name('admin.definition.update');
Route::delete('/admin/definition/delete', [AdminDefinitionController::class, 'delete'])->name('admin.definition.delete');

//HPテキスト管理
Route::get('/admin/hptext', [AdminHpTextController::class, 'index'])->name('admin.hptext');
Route::post('/admin/hptext/store', [AdminHpTextController::class, 'store'])->name('admin.hptext.store');
Route::post('/admin/hptext/update', [AdminHpTextController::class, 'update'])->name('admin.hptext.update');
Route::delete('/admin/hptext/delete', [AdminHpTextController::class, 'delete'])->name('admin.hptext.delete');

// 管理者用ルート
Route::prefix('admin')->name('admin.')->group(function () {
    // ダッシュボード
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // コンテンツスキーマ管理
    Route::get('/content-schema', [AdminContentSchemaController::class, 'index'])->name('content-schema');
    Route::post('/content-schema/add-field', [AdminContentSchemaController::class, 'addField'])->name('content-schema.addField');
    Route::post('/content-schema/update-field', [AdminContentSchemaController::class, 'updateField'])->name('content-schema.updateField');
    Route::delete('/content-schema/delete-field', [AdminContentSchemaController::class, 'deleteField'])->name('content-schema.deleteField');
    Route::post('/content-schema/update-order/{masterId}', [AdminContentSchemaController::class, 'updateOrder'])->name('content-schema.update-order');

    // コンテンツデータ管理
    Route::get('/content-data', [AdminContentDataController::class, 'index'])->name('content-data');
    Route::get('/content-data/master/{masterId}', [AdminContentDataController::class, 'showByMaster'])->name('content-data.master');
    Route::get('/content-data/create/{masterId}', [AdminContentDataController::class, 'create'])->name('content-data.create');
    Route::post('/content-data/store/{masterId}', [AdminContentDataController::class, 'store'])->name('content-data.store');
    Route::get('/content-data/edit/{dataId}', [AdminContentDataController::class, 'edit'])->name('content-data.edit');
    Route::put('/content-data/update/{dataId}', [AdminContentDataController::class, 'update'])->name('content-data.update');
    Route::delete('/content-data/delete/{dataId}', [AdminContentDataController::class, 'delete'])->name('content-data.delete');
    Route::post('/content-data/update-order/{masterId}', [AdminContentDataController::class, 'updateOrder'])->name('content-data.update-order');

    //ファイル削除
    Route::delete('/content-data/delete-file/{dataId}/{fieldName}/{index?}', [AdminContentDataController::class, 'deleteFile'])
        ->name('content-data.delete-file');
});

Route::post('/admin/content-data/toggle-public', [AdminContentDataController::class, 'togglePublic'])->name('admin.content-data.toggle-public');
