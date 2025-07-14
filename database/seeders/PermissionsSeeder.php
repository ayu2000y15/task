<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // パーミッションの基本セット
        $permissions = [
            // 案件関連
            ['name' => 'projects.viewAny', 'display_name' => '案件一覧（閲覧）', 'description' => '案件の一覧を閲覧できます。'],
            ['name' => 'projects.view', 'display_name' => '案件詳細（閲覧）', 'description' => '案件の詳細情報を閲覧できます。'],
            ['name' => 'projects.create', 'display_name' => '案件（作成）', 'description' => '新しい案件を作成できます。'],
            ['name' => 'projects.update', 'display_name' => '案件（更新）', 'description' => '既存の案件情報を編集できます。'],
            ['name' => 'projects.delete', 'display_name' => '案件（削除）', 'description' => '案件を削除できます。'],

            // 工程関連
            ['name' => 'tasks.viewAny', 'display_name' => '工程（閲覧）', 'description' => '工程の一覧を閲覧できます。'],
            ['name' => 'tasks.create', 'display_name' => '工程（作成）', 'description' => '新しい工程を作成できます。'],
            ['name' => 'tasks.update', 'display_name' => '工程（更新）', 'description' => '既存の工程情報を編集できます。'],
            ['name' => 'tasks.delete', 'display_name' => '工程（削除）', 'description' => '工程を削除できます。'],

            ['name' => 'tasks.file-view', 'display_name' => 'ファイル（閲覧）', 'description' => '工程に紐づくファイルを閲覧できます。'],
            ['name' => 'tasks.file-upload', 'display_name' => 'ファイル（アップロード）', 'description' => '工程にファイルをアップロードできます。'],
            ['name' => 'tasks.file-download', 'display_name' => 'ファイル（ダウンロード）', 'description' => '工程のファイルをダウンロードできます。'],

            // 掲示板
            ['name' => 'boards.viewAny', 'display_name' => '掲示板投稿一覧（閲覧）', 'description' => '掲示板投稿の一覧を閲覧できます。'],
            ['name' => 'boards.manage', 'display_name' => '掲示板管理', 'description' => '掲示板投稿の全操作（閲覧・編集・削除等）ができます。'],
            ['name' => 'boards.create', 'display_name' => '掲示板投稿（作成）', 'description' => '掲示板投稿を作成できます。'],
            ['name' => 'boards.update', 'display_name' => '掲示板投稿（編集）', 'description' => '掲示板投稿を編集できます。'],
            ['name' => 'boards.delete', 'display_name' => '掲示板投稿（削除）', 'description' => '掲示板投稿を削除できます。'],

            // ユーザー管理
            ['name' => 'users.viewAny', 'display_name' => 'ユーザー（閲覧）', 'description' => 'ユーザーの一覧を閲覧できます。'],
            ['name' => 'users.view', 'display_name' => 'ユーザー詳細（閲覧）', 'description' => 'ユーザーの詳細情報を閲覧できます。'],
            ['name' => 'users.create', 'display_name' => 'ユーザー（作成）', 'description' => '新しいユーザーを作成できます。'],
            ['name' => 'users.update', 'display_name' => 'ユーザー（ロール編集）', 'description' => 'ユーザーのロールや情報を編集できます。'],
            ['name' => 'users.delete', 'display_name' => 'ユーザー（削除）', 'description' => 'ユーザーを削除できます。'],

            // 権限管理
            ['name' => 'roles.viewAny', 'display_name' => '権限設定（閲覧）', 'description' => '権限設定画面を閲覧できます。'],
            ['name' => 'roles.update', 'display_name' => '権限設定（更新）', 'description' => 'ロールに割り当てる権限を編集できます。'],
            ['name' => 'roles.delete', 'display_name' => '権限設定（削除）', 'description' => 'ロールを削除できます。'],

            ['name' => 'measurements.manage', 'display_name' => '採寸データ管理 (ALL)', 'description' => '全ての採寸データを管理できます。'],
            ['name' => 'measurements.update', 'display_name' => '採寸データ管理 (追加・更新)', 'description' => '採寸データの追加・編集ができます。'],
            ['name' => 'measurements.delete', 'display_name' => '採寸データ管理 (削除)', 'description' => '採寸データを削除できます。'],

            ['name' => 'materials.manage', 'display_name' => '材料リスト管理 (ALL)', 'description' => '全ての材料リストを管理できます。'],
            ['name' => 'materials.update', 'display_name' => '材料リスト管理 (追加・更新)', 'description' => '材料リストの追加・編集ができます。'],
            ['name' => 'materials.delete', 'display_name' => '材料リスト管理 (削除)', 'description' => '材料リストを削除できます。'],

            ['name' => 'costs.manage', 'display_name' => 'コスト管理 (ALL)', 'description' => '全てのコスト情報を管理できます。'],
            ['name' => 'costs.update', 'display_name' => 'コスト管理 (追加・更新)', 'description' => 'コスト情報の追加・編集ができます。'],
            ['name' => 'costs.delete', 'display_name' => 'コスト管理 (削除)', 'description' => 'コスト情報を削除できます。'],

            ['name' => 'process_templates.viewAny', 'display_name' => '工程テンプレート一覧（閲覧）', 'description' => '工程テンプレートの一覧を閲覧できます。'],
            ['name' => 'process_templates.update', 'display_name' => '工程テンプレート（登録、更新）', 'description' => '工程テンプレートの登録・編集ができます。'],
            ['name' => 'process_templates.delete', 'display_name' => '工程テンプレート（削除）', 'description' => '工程テンプレートを削除できます。'],

            ['name' => 'form-definition.viewAny', 'display_name' => '案件依頼項目（閲覧）', 'description' => '案件依頼項目の一覧を閲覧できます。'],
            ['name' => 'form-definition.view', 'display_name' => '案件依頼項目（詳細閲覧）', 'description' => '案件依頼項目の詳細を閲覧できます。'],
            ['name' => 'form-definition.update', 'display_name' => '案件依頼項目（登録、更新）', 'description' => '案件依頼項目の登録・編集ができます。'],
            ['name' => 'form-definition.delete', 'display_name' => '案件依頼項目（削除）', 'description' => '案件依頼項目を削除できます。'],

            ['name' => 'feedback.viewAny', 'display_name' => 'フィードバック（閲覧）', 'description' => 'フィードバックの一覧を閲覧できます。'],
            ['name' => 'feedback.create', 'display_name' => 'フィードバック（登録）', 'description' => '新しいフィードバックを登録できます。'],
            ['name' => 'feedback.update', 'display_name' => 'フィードバック（更新）', 'description' => '既存のフィードバックを編集できます。'],
            ['name' => 'feedback.delete', 'display_name' => 'フィードバック（削除）', 'description' => 'フィードバックを削除できます。'],

            ['name' => 'feedback-category.viewAny', 'display_name' => 'フィードバックカテゴリ（閲覧）', 'description' => 'フィードバックカテゴリの一覧を閲覧できます。'],
            ['name' => 'feedback-category.update', 'display_name' => 'フィードバックカテゴリ（更新）', 'description' => 'フィードバックカテゴリを編集できます。'],
            ['name' => 'feedback-category.delete', 'display_name' => 'フィードバックカテゴリ（削除）', 'description' => 'フィードバックカテゴリを削除できます。'],

            // 在庫品目
            ['name' => 'inventory_items.viewAny', 'display_name' => '在庫品目一覧（閲覧）', 'description' => '在庫品目の一覧を閲覧できます。'],
            ['name' => 'inventory_items.create', 'display_name' => '在庫品目（作成）', 'description' => '在庫品目を新規登録できます。'],
            ['name' => 'inventory_items.update', 'display_name' => '在庫品目（編集）', 'description' => '在庫品目を編集できます。'],
            ['name' => 'inventory_items.delete', 'display_name' => '在庫品目（削除）', 'description' => '在庫品目を削除できます。'],
            ['name' => 'inventory_items.manage_stock', 'display_name' => '在庫操作', 'description' => '在庫の入荷・調整ができます。'],

            // 在庫ログ
            ['name' => 'inventory_logs.delete', 'display_name' => '在庫ログ（削除）', 'description' => '在庫ログを削除できます。'],

            // 在庫発注申請
            ['name' => 'stock_orders.viewAny', 'display_name' => '在庫発注申請一覧（閲覧）', 'description' => '全ての在庫発注申請を閲覧できます。'],
            ['name' => 'stock_orders.view_own', 'display_name' => '自分の在庫発注申請（閲覧）', 'description' => '自分の在庫発注申請を閲覧できます。'],
            ['name' => 'stock_orders.create', 'display_name' => '在庫発注申請（作成）', 'description' => '在庫発注申請を作成できます。'],
            ['name' => 'stock_orders.update', 'display_name' => '在庫発注申請（編集）', 'description' => '在庫発注申請を編集できます。'],
            ['name' => 'stock_orders.delete', 'display_name' => '在庫発注申請（削除）', 'description' => '在庫発注申請を削除できます。'],
            ['name' => 'stock_orders.manage_status', 'display_name' => '在庫発注申請ステータス管理', 'description' => '在庫発注申請のステータスを変更できます。'],

            // 外部案件申請
            ['name' => 'external-submission.viewAny', 'display_name' => '外部案件申請一覧（閲覧）', 'description' => '外部案件申請の一覧を閲覧できます。'],
            ['name' => 'external-submission.update', 'display_name' => '外部案件申請（更新）', 'description' => '外部案件申請のステータス等を更新できます。'],
            ['name' => 'external-submission.delete', 'display_name' => '外部案件申請（削除）', 'description' => '外部案件申請を削除できます。'],

            // ログ
            ['name' => 'log.viewAny', 'display_name' => '操作ログ一覧（閲覧）', 'description' => '操作ログの一覧を閲覧できます。'],

            // 営業ツール・その他
            ['name' => 'tools.viewAnyPage', 'display_name' => 'ツール一覧ページ（閲覧）', 'description' => 'ツール一覧ページを閲覧できます。'],
            ['name' => 'tools.sales.access', 'display_name' => '営業ツール（利用）', 'description' => '営業ツール機能にアクセスできます。'],

            // 生産性・スケジュール・交通費
            ['name' => 'productivity.viewOwn', 'display_name' => '自分の生産性サマリー（閲覧）', 'description' => '自分の生産性サマリーを閲覧できます。'],
            ['name' => 'productivity.viewAll', 'display_name' => '全ユーザーの生産性サマリー（閲覧）', 'description' => '全ユーザーの生産性サマリーを閲覧できます。'],
            ['name' => 'schedules.viewAll', 'display_name' => '全ユーザーのスケジュール（閲覧）', 'description' => '全ユーザーのスケジュールカレンダーを閲覧できます。'],
            ['name' => 'transportation-expenses.viewAll', 'display_name' => '全ユーザーの交通費（閲覧）', 'description' => '全ユーザーの交通費を閲覧できます。'],
            ['name' => 'work-logs.viewAny', 'display_name' => '全作業実績（閲覧）', 'description' => '全ユーザーの作業実績を閲覧できます。'],
            ['name' => 'work-logs.viewOwn', 'display_name' => '自分の作業実績（閲覧）', 'description' => '自分の作業実績を閲覧できます。'],
            ['name' => 'work-logs.view-project-summary', 'display_name' => '案件ごとの作業実績サマリー（閲覧）', 'description' => '案件ごとの作業実績サマリーを閲覧できます。'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate([
                'name' => $permission['name']
            ], [
                'display_name' => $permission['display_name'],
                'description' => $permission['description'],
            ]);
        }
    }
}
