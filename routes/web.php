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

use App\Http\Controllers\CharacterController;
use App\Http\Controllers\ExternalFormController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\BoardPostController;
use App\Http\Controllers\BoardCommentController;

// フィードバック機能で追加するコントローラー
use App\Http\Controllers\Admin\ProcessTemplateController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\UserFeedbackController;
use App\Http\Controllers\Admin\FeedbackController as AdminFeedbackController;
use App\Http\Controllers\Admin\FeedbackCategoryController as AdminFeedbackCategoryController;
use App\Http\Controllers\Admin\LogController as AdminLogController;
use App\Http\Controllers\Admin\FormFieldDefinitionController;
use App\Http\Controllers\Admin\FormCategoryController;
use App\Http\Controllers\Admin\ProjectCategoryController;
use App\Http\Controllers\Admin\BoardPostTypeController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\StockOrderController;
use App\Http\Controllers\Admin\InventoryLogController;
use App\Http\Controllers\Admin\MeasurementTemplateController;
use App\Http\Controllers\Admin\ScheduleController as AdminScheduleController;
use App\Http\Controllers\Admin\TransportationExpenseController as AdminTransportationExpenseController;

use App\Http\Controllers\WorkLogController;
use App\Http\Controllers\WorkRecordController;
use App\Http\Controllers\Admin\WorkRecordController as AdminWorkRecordController;
use App\Http\Controllers\MyHolidayController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ToolController;
use App\Http\Controllers\SalesToolController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\TransportationExpenseController;
use App\Http\Controllers\ShiftChangeRequestController;

Route::middleware('auth')->group(function () {
    // ホーム
    Route::get('/', [HomeController::class, 'index'])->name('home.index');
    Route::get('/dashboard', fn() => redirect()->route('home.index'))->middleware(['auth', 'verified'])->name('dashboard');

    // 案件
    Route::resource('projects', ProjectController::class);
    // 担当者更新用のルート
    Route::post('/projects/{project}/tasks/{task}/assignee', [TaskController::class, 'updateAssignee'])->name('projects.tasks.assignee');
    // プロジェクトフラグ更新用ルート
    Route::patch('/projects/{project}/delivery-flag', [ProjectController::class, 'updateDeliveryFlag'])->name('projects.updateDeliveryFlag');
    Route::patch('/projects/{project}/payment-flag', [ProjectController::class, 'updatePaymentFlag'])->name('projects.updatePaymentFlag');
    // プロジェクトステータス更新用ルート (必要であれば、手動更新用)
    Route::patch('/projects/{project}/status', [ProjectController::class, 'updateStatus'])->name('projects.updateStatus');


    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 工程
    Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::resource('projects.tasks', TaskController::class)->except(['index']);
    Route::post('/projects/{project}/tasks/from-template', [TaskController::class, 'storeFromTemplate'])->name('projects.tasks.fromTemplate');
    Route::post('projects/{project}/tasks/{task}/progress', [TaskController::class, 'updateProgress'])->name('tasks.update_progress');
    Route::post('tasks/update-position', [TaskController::class, 'updatePosition'])->name('tasks.update_position');
    Route::post('tasks/update-parent', [TaskController::class, 'updateParent'])->name('tasks.update_parent');
    Route::post('/projects/{project}/tasks/{task}/assignee', [TaskController::class, 'updateAssignee'])->name('tasks.assignee');
    Route::patch('projects/{project}/tasks/{task}/description', [TaskController::class, 'updateDescription'])->name('projects.tasks.description.update');

    Route::post('/projects/{project}/tasks/{task}/status', [TaskController::class, 'updateStatusFromEdit'])->name('tasks.updateStatusFromEdit');
    Route::post('/projects/{project}/tasks/batch', [App\Http\Controllers\TaskController::class, 'batchStore'])->name('projects.tasks.batchStore');
    Route::post('/projects/{project}/tasks/{task}/rework', [TaskController::class, 'startRework'])->name('tasks.rework.start');

    // ファイル関連のルート
    Route::post('/projects/{project}/tasks/{task}/files', [TaskController::class, 'uploadFiles'])->name('projects.tasks.files.upload');
    Route::get('/projects/{project}/tasks/{task}/files', [TaskController::class, 'getFiles'])->name('projects.tasks.files.index');
    Route::get('/projects/{project}/tasks/{task}/files/{file}/download', [TaskController::class, 'downloadFile'])->name('projects.tasks.files.download');
    Route::get('/projects/{project}/tasks/{task}/files/{file}/show', [TaskController::class, 'showFile'])->name('projects.tasks.files.show');
    Route::delete('/projects/{project}/tasks/{task}/files/{file}', [TaskController::class, 'deleteFile'])->name('projects.tasks.files.destroy');
    Route::post('/projects/{project}/tasks/{task}/files/{file}/toggle-soft-delete', [TaskController::class, 'toggleSoftDeleteFile'])->name('projects.tasks.files.toggleSoftDelete');

    // ガントチャート
    Route::get('gantt', [GanttChartController::class, 'index'])->name('gantt.index');

    // カレンダー
    Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');

    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [\App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');

    // 採寸データ (案件詳細ページ内で処理)
    Route::post('/projects/{project}/characters/{character}/measurements', [MeasurementController::class, 'store'])->name('projects.characters.measurements.store');
    Route::put('/projects/{project}/characters/{character}/measurements/{measurement}', [MeasurementController::class, 'update'])->name('projects.characters.measurements.update');
    Route::delete('/projects/{project}/characters/{character}/measurements/{measurement}', [MeasurementController::class, 'destroy'])->name('projects.characters.measurements.destroy');

    // 採寸テンプレート (案件詳細ページ内で処理)
    Route::get('/projects/{project}/characters/{character}/measurement-templates', [MeasurementTemplateController::class, 'indexForCharacter'])->name('projects.characters.measurement-templates.index');
    Route::post('/projects/{project}/characters/{character}/measurement-templates', [MeasurementTemplateController::class, 'storeForCharacter'])->name('projects.characters.measurement-templates.store');
    Route::get('/measurement-templates/{measurement_template}/load', [MeasurementTemplateController::class, 'load'])->name('measurement-templates.load');
    Route::delete('/measurement-templates/{measurement_template}', [MeasurementTemplateController::class, 'destroy'])->name('measurement-templates.destroy');
    Route::post('/projects/{project}/characters/{character}/measurements/update-order', [MeasurementController::class, 'updateOrder'])->name('projects.characters.measurements.updateOrder');
    Route::post('/projects/{project}/characters/{character}/measurements/batch', [App\Http\Controllers\MeasurementController::class, 'batchStore'])->name('projects.characters.measurements.batchStore');

    // 材料データ (案件詳細ページ内で処理)
    Route::post('/projects/{project}/characters/{character}/materials', [MaterialController::class, 'store'])->name('projects.characters.materials.store');
    Route::put('/projects/{project}/characters/{character}/materials/{material}', [MaterialController::class, 'update'])->name('projects.characters.materials.update');
    Route::delete('/projects/{project}/characters/{character}/materials/{material}', [MaterialController::class, 'destroy'])->name('projects.characters.materials.destroy');
    Route::post('/projects/{project}/characters/{character}/materials/update-order', [MaterialController::class, 'updateOrder'])->name('projects.characters.materials.updateOrder');


    // コストデータ (案件詳細ページ内で処理)
    Route::post('/projects/{project}/characters/{character}/costs', [CostController::class, 'store'])->name('projects.characters.costs.store');
    Route::put('/projects/{project}/characters/{character}/costs/{cost}', [CostController::class, 'update'])->name('projects.characters.costs.update');
    Route::delete('/projects/{project}/characters/{character}/costs/{cost}', [CostController::class, 'destroy'])->name('projects.characters.costs.destroy');
    Route::post('/projects/{project}/characters/{character}/costs/update-order', [CostController::class, 'updateOrder'])->name('projects.characters.costs.updateOrder');

    // キャラクター管理 (案件詳細ページ内で処理の起点、編集は別ページ)
    Route::resource('projects.characters', CharacterController::class)->except(['index', 'show', 'create'])->shallow();
    Route::get('/projects/{project}/characters/{character}/costs-partial', [CharacterController::class, 'getCharacterCostsPartial'])->name('projects.characters.costs.partial');
    Route::post('/projects/{project}/completion-folders', [ProjectController::class, 'storeCompletionFolder'])->name('projects.completionFolders.store');

    Route::post('/projects/{project}/characters/update-order', [App\Http\Controllers\CharacterController::class, 'updateOrder'])->name('characters.updateOrder');    // --- ユーザー向けフィードバック機能 ---

    Route::patch('/characters/{character}/measurement-notes', [CharacterController::class, 'updateMeasurementNotes'])->name('characters.updateMeasurementNotes');

    Route::get('/feedback/create', [UserFeedbackController::class, 'create'])->name('user_feedbacks.create');
    Route::post('/feedback', [UserFeedbackController::class, 'store'])->name('user_feedbacks.store');
    // --- ここまでユーザー向けフィードバック機能 ---

    //作業依頼
    Route::get('requests', [RequestController::class, 'index'])->name('requests.index');
    Route::get('requests/create', [RequestController::class, 'create'])->name('requests.create');
    Route::post('requests', [RequestController::class, 'store'])->name('requests.store');
    Route::patch('requests/items/{item}', [RequestController::class, 'updateItem'])->name('requests.items.update');
    Route::get('requests/{request}/edit', [RequestController::class, 'edit'])->name('requests.edit');
    Route::patch('requests/{request}', [RequestController::class, 'update'])->name('requests.update');
    Route::delete('requests/{request}', [RequestController::class, 'destroy'])->name('requests.destroy');

    Route::post('/requests/items/{item}/set-start-at', [RequestController::class, 'updateItemStartAt'])->name('requests.items.setStartAt');
    Route::post('/requests/items/{item}/set-end-at', [RequestController::class, 'updateItemEndAt'])->name('requests.items.setEndAt');
    Route::post('/requests/{request}/items/update-order', [RequestController::class, 'updateItemOrder'])->name('requests.items.updateOrder');
    // -------------------------------------------------------------------------
    // 社内掲示板
    // -------------------------------------------------------------------------
    Route::prefix('community')->name('community.')->middleware(['can:viewAny,App\Models\BoardPost'])->group(function () {
        Route::get('posts/{post}/search-users', [BoardPostController::class, 'searchMentionableUsers'])
            ->name('posts.search-users');

        // カスタム項目取得用のルート（resourceより前に定義）
        Route::get('posts/custom-fields', [BoardPostController::class, 'getCustomFields'])->name('posts.customFields');
        // 画像アップロード (TinyMCE用)
        Route::post('posts/upload-image', [BoardPostController::class, 'uploadImage'])->name('posts.uploadImage');
        // メンション用のユーザー検索ルート
        Route::get('/users/search', [BoardPostController::class, 'searchUsers'])->name('users.search');

        Route::resource('posts', BoardPostController::class);
        // コメント投稿
        Route::post('posts/{post}/comments', [BoardCommentController::class, 'store'])->name('posts.comments.store');
        // コメント更新・削除・リアクション用のルート
        Route::patch('comments/{comment}', [BoardCommentController::class, 'update'])->name('comments.update');
        Route::delete('comments/{comment}', [BoardCommentController::class, 'destroy'])->name('comments.destroy');
        Route::post('comments/{comment}/reactions', [BoardCommentController::class, 'toggleReaction'])->name('comments.reactions.store');
        // リアクション用のルート
        Route::post('posts/{post}/reactions', [BoardPostController::class, 'toggleReaction'])->name('posts.reactions.store');
    });

    // 時間記録のアクション
    Route::post('/work-logs/start', [WorkLogController::class, 'start'])->name('work-logs.start');
    // Route::post('/work-logs/{workLog}/stop', [WorkLogController::class, 'stop'])->name('work-logs.stop');
    Route::post('/work-logs/start', [App\Http\Controllers\WorkLogController::class, 'start'])->name('work-logs.start');
    Route::post('/work-logs/stop-by-task', [App\Http\Controllers\WorkLogController::class, 'stopByTask'])->name('work-logs.stop-by-task');

    // 外部からの管理連絡先登録
    Route::get('/contact-register', [ExternalFormController::class, 'createContact'])->name('external-contact.create');
    Route::post('/contact-register', [ExternalFormController::class, 'storeContact'])->name('external-contact.store');
    Route::post('/check-email-external', [ExternalFormController::class, 'checkEmail'])->name('external-contact.checkEmail');


    // 作業実績ページ
    Route::get('/my-work-records', [WorkRecordController::class, 'index'])->name('work-records.index');

    Route::patch('/my-work-records/{workLog}/time', [WorkRecordController::class, 'updateTime'])->name('work-records.update-time');
    Route::delete('/my-work-records/{workLog}/time', [WorkRecordController::class, 'resetTime'])->name('work-records.reset-time');


    // 打刻API
    Route::post('/attendance/clock', [AttendanceController::class, 'clock'])->name('attendance.clock');
    // 打刻時に予定と違う場所に出勤する場合、work_shifts を更新/作成するためのエンドポイント
    Route::post('/attendance/change-location-on-clockin', [AttendanceController::class, 'changeLocationOnClockIn'])->name('attendance.changeLocationOnClockIn');

    // シフト登録
    Route::get('schedule', [ShiftController::class, 'monthlySchedule'])->name('schedule.monthly');
    Route::post('schedule/update', [ShiftController::class, 'updateOrClearDay'])->name('schedule.updateOrClearDay');
    // デフォルトパターン設定
    Route::get('shifts/default', [ShiftController::class, 'editDefault'])->name('shifts.default.edit');
    Route::post('shifts/default', [ShiftController::class, 'updateDefault'])->name('shifts.default.update');

    Route::get('/api/schedule/events', [AdminScheduleController::class, 'fetchEvents'])->name('api.schedule.events');

    Route::get('/shift-change-requests', [ShiftChangeRequestController::class, 'index'])->name('shift-change-requests.index');
    Route::post('/shift-change-requests', [ShiftChangeRequestController::class, 'store'])->name('shift-change-requests.store');
    Route::post('/shift-change-requests/{shiftChangeRequest}/approve', [ShiftChangeRequestController::class, 'approve'])->name('shift-change-requests.approve');
    Route::post('/shift-change-requests/{shiftChangeRequest}/reject', [ShiftChangeRequestController::class, 'reject'])->name('shift-change-requests.reject');
    Route::get('/my-shift-requests', [ShiftChangeRequestController::class, 'myRequests'])->name('shift-change-requests.my');
    Route::delete('/shift-change-requests/{shiftChangeRequest}', [ShiftChangeRequestController::class, 'destroy'])->name('shift-change-requests.destroy');


    // 交通費登録
    Route::resource('transportation-expenses', TransportationExpenseController::class)->except(['show']);
    Route::post('/transportation-expenses/batch-store', [TransportationExpenseController::class, 'batchStore'])->name('transportation-expenses.batch-store');

    Route::prefix('admin')->name('admin.')->middleware(['can:viewAny,App\Models\ProcessTemplate'])->group(function () { // 管理者用などのミドルウェアを想定
        // 投稿タイプ管理
        Route::resource('board-post-types', BoardPostTypeController::class);
        Route::post('/board-post-types/update-order', [BoardPostTypeController::class, 'updateOrder'])->name('board-post-types.update-order');

        // フォームフィールド定義管理
        Route::post('/form-definitions/reorder', [FormFieldDefinitionController::class, 'reorder'])->name('form-definitions.reorder');
        Route::post('/form-definitions/upload-image', [FormFieldDefinitionController::class, 'uploadImage'])->name('form-definitions.uploadImage');
        Route::post('/form-definitions/{formFieldDefinition}/options', [FormFieldDefinitionController::class, 'updateOptions'])->name('form-definitions.updateOptions');
        Route::resource('form-definitions', FormFieldDefinitionController::class)
            ->parameters(['form-definitions' => 'formFieldDefinition'])
            ->except(['show']);
        // フォームカテゴリ管理
        Route::resource('form-categories', FormCategoryController::class)
            ->parameters(['form-categories' => 'formCategory']);
        Route::post('/form-categories/reorder', [FormCategoryController::class, 'reorder'])->name('form-categories.reorder');
        Route::patch('/form-categories/{formCategory}/toggle-external', [FormCategoryController::class, 'toggleExternalForm'])->name('form-categories.toggle-external');

        // 案件カテゴリ管理
        Route::resource('project-categories', ProjectCategoryController::class)
            ->parameters(['project-categories' => 'projectCategory']);

        // 工程テンプレート管理
        Route::resource('process-templates', ProcessTemplateController::class);
        Route::post('process-templates/{processTemplate}/items', [ProcessTemplateController::class, 'storeItem'])->name('process-templates.items.store');
        Route::delete('process-templates/{processTemplate}/items/{item}', [ProcessTemplateController::class, 'destroyItem'])->name('process-templates.items.destroy');
        Route::put('process-templates/{process_template}/items/{item}', [ProcessTemplateController::class, 'itemsUpdate'])->name('process-templates.items.update');

        // 採寸テンプレート管理
        Route::resource('measurement-templates', MeasurementTemplateController::class)->except(['show']); // showはeditと統合する形で今回は作成
        // MeasurementTemplateControllerにcreate, store, edit, update, destroy, indexメソッドが必要になる
        // 既存のload, storeForCharacter, indexForCharacterは別用途
        // もし項目編集を別画面やモーダルで行う場合は、それ用のルートも必要
        // 今回はシンプルに、テンプレート名と説明を編集し、項目はJSONで直接管理されていると想定
        // もしテンプレートの「項目」自体を編集するUIを設けるなら、show.blade.phpでそれを行う
        Route::get('measurement-templates/{measurement_template}/edit', [MeasurementTemplateController::class, 'edit'])->name('measurement-templates.edit'); // editを明示的に定義
        Route::get('measurement-templates/create', [MeasurementTemplateController::class, 'create'])->name('measurement-templates.create');


        // ユーザー管理・権限設定 (既存のルート)
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::resource('roles', RolePermissionController::class);


        // --- 管理者向けフィードバック機能 ---
        Route::get('/feedbacks', [AdminFeedbackController::class, 'index'])->name('feedbacks.index');
        Route::get('/feedbacks/{feedback}/edit', [AdminFeedbackController::class, 'edit'])->name('feedbacks.edit'); // ★ 編集画面表示
        Route::put('/feedbacks/{feedback}', [AdminFeedbackController::class, 'update'])->name('feedbacks.update'); // ★ 更新処理

        // AJAX用ルート
        Route::patch('/feedbacks/{feedback}/status', [AdminFeedbackController::class, 'updateStatus'])->name('feedbacks.updateStatus');
        Route::patch('/feedbacks/{feedback}/memo', [AdminFeedbackController::class, 'updateMemo'])->name('feedbacks.updateMemo');
        Route::patch('/feedbacks/{feedback}/assignee', [AdminFeedbackController::class, 'updateAssignee'])->name('feedbacks.updateAssignee'); // ★ 担当者更新ルート追加

        // フィードバックカテゴリ管理
        Route::resource('feedback-categories', AdminFeedbackCategoryController::class)->except(['show']);
        Route::resource('feedback-categories', AdminFeedbackCategoryController::class)->except(['show']);
        Route::post('feedback-categories/reorder', [AdminFeedbackCategoryController::class, 'reorder'])->name('feedback-categories.reorder'); // ★ 並び替え用ルート追加
        // --- ここまで管理者向けフィードバック機能 ---

        // 在庫管理
        Route::get('/inventory/search', [\App\Http\Controllers\Admin\InventoryController::class, 'searchApi'])->name('inventory.search_api');

        Route::get('inventory/bulk-create', [InventoryController::class, 'bulkCreate'])->name('inventory.bulk-create');
        Route::post('inventory/bulk-store', [InventoryController::class, 'bulkStore'])->name('inventory.bulk-store');

        Route::resource('inventory', InventoryController::class)->parameters(['inventory' => 'inventoryItem']);
        Route::post('inventory/{inventoryItem}/stock-in', [InventoryController::class, 'stockIn'])->name('inventory.stockIn');
        Route::post('inventory/{inventoryItem}/adjust', [InventoryController::class, 'adjustStock'])->name('inventory.adjustStock');


        // 在庫発注申請
        Route::resource('stock-orders', StockOrderController::class);
        Route::patch('stock-orders/{stockOrder}/status', [StockOrderController::class, 'updateStatus'])->name('stock-orders.updateStatus');

        Route::get('inventory-logs', [InventoryLogController::class, 'index'])->name('inventory-logs.index');


        // ★ 操作ログ閲覧ルートを追加
        Route::get('/logs', [AdminLogController::class, 'index'])->name('logs.index');

        // ★ 案件依頼フォーム一覧ルートを追加
        Route::get('/external-submissions', [ExternalFormController::class, 'index'])->name('external-submissions.index');
        Route::patch('/external-submissions/{submission}/status', [ExternalFormController::class, 'updateStatus'])->name('external-submissions.updateStatus'); // ステータス更新用
        Route::get('/external-submissions/{submission}', [ExternalFormController::class, 'show'])->name('external-submissions.show'); // 詳細表示用

        Route::get('/external-requests', [ExternalFormController::class, 'index'])->name('external-requests.index');

        // 作業実績管理
        Route::get('/work-records/by-project', [AdminWorkRecordController::class, 'byProject'])->name('work-records.by-project');
        Route::get('/work-records/daily-log', [AdminWorkRecordController::class, 'dailyLog'])->name('work-records.daily-log');
        Route::get('/work-records', [AdminWorkRecordController::class, 'index'])->name('work-records.index');
        Route::post('work-records/update-rates', [AdminWorkRecordController::class, 'updateUserRates'])->name('work-records.update-rates');
        Route::get('/work-records/{user}', [AdminWorkRecordController::class, 'show'])->name('work-records.show');

        // 勤怠管理ルート
        Route::get('/attendances/{user}/{month?}', [AdminAttendanceController::class, 'show'])->name('attendances.show');
        Route::post('/attendances/generate/{user}/{month}', [AdminAttendanceController::class, 'generate'])->name('attendances.generate');
        Route::post('/attendances/{user}/{date}', [AdminAttendanceController::class, 'updateSingle'])->name('attendances.update-single');

        // 全員のスケジュールカレンダー
        Route::get('/schedule/calendar', [AdminScheduleController::class, 'calendar'])->name('schedule.calendar');
        // 交通費一覧
        Route::get('/transportation-expenses', [AdminTransportationExpenseController::class, 'index'])->name('transportation-expenses.index');
    });

    // -------------------------------------------------------------------------
    // 営業ツール
    // -------------------------------------------------------------------------
    // 営業ツールへのアクセス権限は 'access_sales_tools' と仮定します。
    // 必要に応じて権限名を変更し、AuthServiceProviderなどで定義してください。
    Route::prefix('tools')->name('tools.')->middleware(['can:tools.viewAnyPage'])->group(function () {
        Route::get('/', [ToolController::class, 'index'])
            ->name('index')
            ->middleware(['can:tools.viewAnyPage']);

        // ---------------------------------------------------------------------
        // 営業ツール機能 (ツール一覧の下の個別ツールとして)
        // ---------------------------------------------------------------------
        Route::prefix('sales')->name('sales.')->group(function () {
            Route::get('/', [SalesToolController::class, 'index'])->name('index')->middleware(['can:tools.sales.access']);

            // メールリスト管理
            Route::prefix('email-lists')->name('email-lists.')->group(function () {
                Route::get('/', [SalesToolController::class, 'emailListsIndex'])->name('index');
                Route::get('/create', [SalesToolController::class, 'emailListsCreate'])->name('create');
                Route::post('/', [SalesToolController::class, 'emailListsStore'])->name('store');
                // Route::get('/{emailList}', [SalesToolController::class, 'emailListsShow'])->name('show');
                Route::delete('/{emailList}/subscribers/destroy-all', [SalesToolController::class, 'subscribersDestroyAll'])->name('subscribers.destroy-all');
                Route::get('/{emailList}/edit', [SalesToolController::class, 'emailListsEdit'])->name('edit');
                Route::put('/{emailList}', [SalesToolController::class, 'emailListsUpdate'])->name('update');
                Route::delete('/{emailList}', [SalesToolController::class, 'emailListsDestroy'])->name('destroy');
                // メールリスト詳細 (購読者一覧)
                Route::get('/{emailList}', [SalesToolController::class, 'emailListsShow'])->name('show'); // メールリスト詳細 (購読者一覧)

                Route::prefix('/{emailList}/subscribers')->name('subscribers.')->group(function () {
                    Route::get('/create', [SalesToolController::class, 'subscribersCreate'])->name('create');
                    Route::post('/', [SalesToolController::class, 'subscribersStore'])->name('store');
                    Route::get('/{subscriber}/edit', [SalesToolController::class, 'subscribersEdit'])->name('edit');
                    Route::put('/{subscriber}', [SalesToolController::class, 'subscribersUpdate'])->name('update');
                    Route::delete('/{subscriber}', [SalesToolController::class, 'subscribersDestroy'])->name('destroy');
                });
            });

            // 管理連絡先管理
            Route::prefix('managed-contacts')->name('managed-contacts.')->middleware(['can:tools.sales.access'])->group(function () {
                Route::get('/', [SalesToolController::class, 'managedContactsIndex'])->name('index');
                Route::get('/create', [SalesToolController::class, 'managedContactsCreate'])->name('create');
                Route::post('/', [SalesToolController::class, 'managedContactsStore'])->name('store');
                Route::get('/{managedContact}/edit', [SalesToolController::class, 'managedContactsEdit'])->name('edit');
                Route::put('/{managedContact}', [SalesToolController::class, 'managedContactsUpdate'])->name('update');
                Route::delete('/{managedContact}', [SalesToolController::class, 'managedContactsDestroy'])->name('destroy');
                Route::post('/import', [SalesToolController::class, 'importContacts'])->name('import');
                // 必要に応じて検索やフィルター用のルートもここに追加
                // メールアドレスのリアルタイム重複チェック用ルート
                Route::post('/check-email', [SalesToolController::class, 'checkEmail'])->name('checkEmail');
            });

            // メール送信
            Route::prefix('emails')->name('emails.')->group(function () { // tools.sales.emails.
                Route::get('/compose', [SalesToolController::class, 'composeEmail'])->name('compose'); // ★★★ このルート ★★★
                Route::post('/send', [SalesToolController::class, 'sendEmail'])->name('send');
                // Route::get('/sent', [SalesToolController::class, 'sentEmailsIndex'])->name('sent.index'); // 送信履歴一覧 (今後作成)
                Route::post('/upload-image', [SalesToolController::class, 'uploadImageForTinyMCE'])->name('uploadImage');
                Route::get('/sent', [SalesToolController::class, 'sentEmailsIndex'])->name('sent.index');
                Route::get('/sent/{sentEmail}', [SalesToolController::class, 'sentEmailsShow'])->name('sent.show'); // ルートモデルバインディング

            });

            // ブラックリスト管理
            Route::prefix('blacklist')->name('blacklist.')->group(function () { // middlewareは親(sales)で適用済み
                Route::get('/', [SalesToolController::class, 'blacklistIndex'])->name('index');
                Route::post('/', [SalesToolController::class, 'blacklistStore'])->name('store');
                Route::delete('/{blacklistEntry}', [SalesToolController::class, 'blacklistDestroy'])->name('destroy');
            });

            // メール送信設定
            Route::prefix('settings')->name('settings.')->group(function () { // middlewareは親(sales)で適用済み
                Route::get('/', [SalesToolController::class, 'settingsEdit'])->name('edit'); // 設定画面は編集フォームのみ
                Route::put('/', [SalesToolController::class, 'settingsUpdate'])->name('update');
            });

            // メールテンプレート
            Route::prefix('templates')->name('templates.')->group(function () {
                Route::get('/', [SalesToolController::class, 'templatesIndex'])->name('index');
                Route::get('/create', [SalesToolController::class, 'templatesCreate'])->name('create');
                Route::post('/', [SalesToolController::class, 'templatesStore'])->name('store');
                Route::get('/{template}/edit', [SalesToolController::class, 'templatesEdit'])->name('edit');
                Route::put('/{template}', [SalesToolController::class, 'templatesUpdate'])->name('update');
                Route::delete('/{template}', [SalesToolController::class, 'templatesDestroy'])->name('destroy');
                // AJAX用: テンプレート内容取得
                Route::get('/{template}/content', [SalesToolController::class, 'getEmailTemplateContent'])->name('content.json');
            });
        });
    });
});

Route::prefix('track')->name('track.')->group(function () {
    Route::get('/open/{identifier}', [TrackingController::class, 'open'])->name('open');
    Route::get('/click/{identifier}', [TrackingController::class, 'click'])->name('click');
});

// ★ 配信停止関連のルートをグループの外に単独で定義します ★
Route::get('/unsubscribe/confirm/{identifier}', [TrackingController::class, 'showUnsubscribeConfirmationPage'])->name('unsubscribe.confirm');
Route::post('/unsubscribe/process', [TrackingController::class, 'processUnsubscribe'])->name('unsubscribe.process');


// 外部向け申請フォーム (認証外)
// Route::get('/costume-request', [ExternalFormController::class, 'create'])->name('external-form.create');
// Route::post('/costume-request', [ExternalFormController::class, 'store'])->name('external-form.store');
Route::get('/contact-register/thanks', [ExternalFormController::class, 'thanks'])->name('external-form.thanks');

// 動的外部フォーム (認証外)
Route::get('/form/{slug}', [ExternalFormController::class, 'showDynamicForm'])->name('external-form.show');
Route::post('/form/{slug}/confirm', [ExternalFormController::class, 'confirmDynamicForm'])->name('external-form.confirm');
Route::post('/form/{slug}/complete', [ExternalFormController::class, 'storeDynamicForm'])->name('external-form.store');
Route::get('/form/{slug}/thanks', [ExternalFormController::class, 'showDynamicThanks'])->name('external-form.thanks');

require __DIR__ . '/auth.php';
