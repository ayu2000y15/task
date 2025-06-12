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
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\StockOrderController;
use App\Http\Controllers\Admin\InventoryLogController;
use App\Http\Controllers\Admin\MeasurementTemplateController;

use App\Http\Controllers\WorkLogController;
use App\Http\Controllers\WorkRecordController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\Admin\WorkRecordController as AdminWorkRecordController;


use App\Http\Controllers\ToolController;
use App\Http\Controllers\SalesToolController;

Route::middleware('auth')->group(function () {
    // ホーム
    Route::get('/', [HomeController::class, 'index'])->name('home.index');
    Route::get('/dashboard', fn() => redirect()->route('home.index'))->middleware(['auth', 'verified'])->name('dashboard');

    // 衣装案件
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

    // --- ユーザー向けフィードバック機能 ---
    Route::get('/feedback/create', [UserFeedbackController::class, 'create'])->name('user_feedbacks.create');
    Route::post('/feedback', [UserFeedbackController::class, 'store'])->name('user_feedbacks.store');
    // --- ここまでユーザー向けフィードバック機能 ---

    // -------------------------------------------------------------------------
    // 社内掲示板
    // -------------------------------------------------------------------------
    Route::prefix('community')->name('community.')->middleware(['can:viewAny,App\Models\BoardPost'])->group(function () {
        Route::resource('posts', BoardPostController::class);

        // コメント投稿
        Route::post('posts/{post}/comments', [BoardCommentController::class, 'store'])->name('posts.comments.store');
        // メンション用のユーザー検索ルート
        Route::get('/users/search', [BoardPostController::class, 'searchUsers'])->name('users.search');
        // 画像アップロード (TinyMCE用)
        Route::post('posts/upload-image', [BoardPostController::class, 'uploadImage'])->name('posts.uploadImage');
        // コメント更新・削除・リアクション用のルート
        Route::patch('comments/{comment}', [BoardCommentController::class, 'update'])->name('comments.update');
        Route::delete('comments/{comment}', [BoardCommentController::class, 'destroy'])->name('comments.destroy');
        Route::post('comments/{comment}/reactions', [BoardCommentController::class, 'toggleReaction'])->name('comments.toggleReaction');
        // リアクション用のルート
        Route::post('posts/{post}/reactions', [BoardPostController::class, 'toggleReaction'])->name('posts.toggleReaction');
    });

    // 時間記録のアクション
    Route::post('/work-logs/start', [WorkLogController::class, 'start'])->name('work-logs.start');
    Route::post('/work-logs/{workLog}/stop', [WorkLogController::class, 'stop'])->name('work-logs.stop');

    // 作業実績ページ
    Route::get('/my-work-records', [WorkRecordController::class, 'index'])->name('work-records.index');

    // 休暇管理
    Route::resource('leaves', LeaveController::class);

    Route::prefix('admin')->name('admin.')->middleware(['can:viewAny,App\Models\ProcessTemplate'])->group(function () { // 管理者用などのミドルウェアを想定
        Route::resource('form-definitions', FormFieldDefinitionController::class)
            ->parameters(['form-definitions' => 'formFieldDefinition'])
            ->except(['show']);
        Route::post('/form-definitions/reorder', [FormFieldDefinitionController::class, 'reorder'])->name('form-definitions.reorder');

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
        Route::get('roles', [RolePermissionController::class, 'index'])->name('roles.index');
        Route::put('roles/{role}', [RolePermissionController::class, 'update'])->name('roles.update');


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

        // ★ 衣装案件依頼フォーム一覧ルートを追加
        Route::get('/external-submissions', [ExternalFormController::class, 'index'])->name('external-submissions.index');
        Route::patch('/external-submissions/{submission}/status', [ExternalFormController::class, 'updateStatus'])->name('external-submissions.updateStatus'); // ステータス更新用
        Route::get('/external-submissions/{submission}', [ExternalFormController::class, 'show'])->name('external-submissions.show'); // 詳細表示用

        Route::get('/external-requests', [ExternalFormController::class, 'index'])->name('external-requests.index');

        // 作業実績管理
        Route::get('/work-records/by-project', [AdminWorkRecordController::class, 'byProject'])->name('work-records.by-project');

        Route::get('/work-records', [AdminWorkRecordController::class, 'index'])->name('work-records.index');
        Route::post('/work-records/update-rate', [AdminWorkRecordController::class, 'updateUserRate'])->name('work-records.update-rate');
        Route::get('/work-records/{user}', [AdminWorkRecordController::class, 'show'])->name('work-records.show');
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
                Route::post('/import-csv', [SalesToolController::class, 'managedContactsImportCsv'])->name('importCsv'); // ★ この行を追加 ★

                // 必要に応じて検索やフィルター用のルートもここに追加
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
    Route::get('/unsubscribe/{identifier}/{list_hash?}', [TrackingController::class, 'unsubscribe'])->name('unsubscribe');
});

// 外部向け申請フォーム (認証外)
Route::get('/costume-request', [ExternalFormController::class, 'create'])->name('external-form.create');
Route::post('/costume-request', [ExternalFormController::class, 'store'])->name('external-form.store');
Route::get('/costume-request/thanks', [ExternalFormController::class, 'thanks'])->name('external-form.thanks');


require __DIR__ . '/auth.php';
