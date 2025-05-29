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
use App\Http\Controllers\ProcessTemplateController;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\Admin\FormFieldDefinitionController;
use App\Http\Controllers\ExternalFormController;

Route::middleware('auth')->group(function () {
    // ホーム
    Route::get('/', [HomeController::class, 'index'])->name('home.index');
    Route::get('/dashboard', fn() => redirect()->route('home.index'))->middleware(['auth', 'verified'])->name('dashboard');

    // 衣装案件
    Route::resource('projects', ProjectController::class);
    // 担当者更新用のルート
    Route::post('/projects/{project}/tasks/{task}/assignee', [TaskController::class, 'updateAssignee'])->name('projects.tasks.assignee');
    // プロジェクトフラグ更新用ルート
    Route::patch('/projects/{project}/delivery-flag', [App\Http\Controllers\ProjectController::class, 'updateDeliveryFlag'])->name('projects.updateDeliveryFlag');
    Route::patch('/projects/{project}/payment-flag', [App\Http\Controllers\ProjectController::class, 'updatePaymentFlag'])->name('projects.updatePaymentFlag');
    // プロジェクトステータス更新用ルート (必要であれば、手動更新用)
    Route::patch('/projects/{project}/status', [App\Http\Controllers\ProjectController::class, 'updateStatus'])->name('projects.updateStatus');


    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 工程
    Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
    // Route::get('tasks/export', [TaskController::class, 'export'])->name('tasks.export'); // exportメソッドが見当たらないためコメントアウト
    Route::resource('projects.tasks', TaskController::class)->except(['index']);
    Route::post('/projects/{project}/tasks/from-template', [TaskController::class, 'storeFromTemplate'])->name('projects.tasks.fromTemplate');
    Route::post('projects/{project}/tasks/{task}/progress', [TaskController::class, 'updateProgress'])->name('tasks.update_progress');
    Route::post('tasks/update-position', [TaskController::class, 'updatePosition'])->name('tasks.update_position');
    Route::post('tasks/update-parent', [TaskController::class, 'updateParent'])->name('tasks.update_parent');

    Route::post('/projects/{project}/tasks/{task}/assignee', [TaskController::class, 'updateAssignee'])->name('tasks.assignee');

    // 工程のメモ更新用ルート
    Route::patch('projects/{project}/tasks/{task}/description', [TaskController::class, 'updateDescription'])->name('projects.tasks.description.update');

    // ファイル関連のルート
    Route::post('/projects/{project}/tasks/{task}/files', [TaskController::class, 'uploadFiles'])->name('projects.tasks.files.upload');
    Route::get('/projects/{project}/tasks/{task}/files', [TaskController::class, 'getFiles'])->name('projects.tasks.files.index');
    Route::get('/projects/{project}/tasks/{task}/files/{file}/download', [TaskController::class, 'downloadFile'])->name('projects.tasks.files.download');
    Route::get('/projects/{project}/tasks/{task}/files/{file}/show', [TaskController::class, 'showFile'])->name('projects.tasks.files.show');
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
    Route::post('/projects/{project}/characters/{character}/measurements', [MeasurementController::class, 'store'])->name('projects.characters.measurements.store');
    Route::delete('/projects/{project}/characters/{character}/measurements/{measurement}', [MeasurementController::class, 'destroy'])->name('projects.characters.measurements.destroy');

    // 材料データ (案件詳細ページ内で処理)
    Route::post('/projects/{project}/characters/{character}/materials', [MaterialController::class, 'store'])->name('projects.characters.materials.store');
    Route::patch('/projects/{project}/characters/{character}/materials/{material}', [MaterialController::class, 'update'])->name('projects.characters.materials.update');
    Route::delete('/projects/{project}/characters/{character}/materials/{material}', [MaterialController::class, 'destroy'])->name('projects.characters.materials.destroy');

    // コストデータ (案件詳細ページ内で処理)
    Route::post('/projects/{project}/characters/{character}/costs', [CostController::class, 'store'])->name('projects.characters.costs.store');
    Route::delete('/projects/{project}/characters/{character}/costs/{cost}', [CostController::class, 'destroy'])->name('projects.characters.costs.destroy');

    // 工程テンプレート管理
    Route::resource('process-templates', ProcessTemplateController::class);
    Route::post('process-templates/{processTemplate}/items', [ProcessTemplateController::class, 'storeItem'])->name('process-templates.items.store');
    // Route::put('process-templates/{processTemplate}/items/{item}', [ProcessTemplateController::class, 'updateItem'])->name('process-templates.items.update'); // 今回はシンプルにするため更新は省略
    Route::delete('process-templates/{processTemplate}/items/{item}', [ProcessTemplateController::class, 'destroyItem'])->name('process-templates.items.destroy');

    // キャラクター管理 (案件詳細ページ内で処理の起点、編集は別ページ)
    Route::resource('projects.characters', CharacterController::class)->except(['index', 'show', 'create'])->shallow();;
    Route::get('/projects/{project}/characters/{character}/costs-partial', [CharacterController::class, 'getCharacterCostsPartial'])->name('projects.characters.costs.partial');

    // 管理機能
    Route::get('/users', [\App\Http\Controllers\UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/edit', [\App\Http\Controllers\UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [\App\Http\Controllers\UserController::class, 'update'])->name('users.update');
    Route::get('/roles', [\App\Http\Controllers\RolePermissionController::class, 'index'])->name('roles.index');
    Route::put('/roles/{role}', [\App\Http\Controllers\RolePermissionController::class, 'update'])->name('roles.update');

    Route::prefix('admin')->name('admin.')->middleware(['can:viewAny,App\Models\FormFieldDefinition'])->group(function () { // 管理者用などのミドルウェアを想定
        Route::resource('form-definitions', FormFieldDefinitionController::class)
            ->parameters(['form-definitions' => 'formFieldDefinition']) // ★ この行を追加
            ->except(['show']);
        Route::post('/form-definitions/reorder', [FormFieldDefinitionController::class, 'reorder'])->name('form-definitions.reorder');
    });

    // 外部向け申請フォーム
    Route::get('/costume-request', [ExternalFormController::class, 'create'])->name('external-form.create');
    Route::post('/costume-request', [ExternalFormController::class, 'store'])->name('external-form.store');
    Route::get('/costume-request/thanks', [ExternalFormController::class, 'thanks'])->name('external-form.thanks');
});

require __DIR__ . '/auth.php';
