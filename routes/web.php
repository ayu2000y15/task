<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\GanttChartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CalendarController;

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
