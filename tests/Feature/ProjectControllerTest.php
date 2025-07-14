<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use App\Models\FormFieldDefinition;
use App\Models\ExternalProjectSubmission;
use App\Models\Cost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用の認証済みユーザーを作成
        $this->user = User::factory()->create();

        // テストに必要な設定値をセットアップ
        config(['shipping.carriers' => ['test_carrier' => 'Test Carrier', 'yamato' => 'ヤマト運輸']]);

        // spatie/laravel-activitylog を有効にする
        Activity::flushEventListeners();
        config()->set('activitylog.enabled', true);
    }

    /**
     * ゲストはプロジェクト関連のページにアクセスできない
     */
    public function test_guests_cannot_access_project_pages()
    {
        $project = Project::factory()->create();

        $this->get(route('projects.index'))->assertRedirect('login');
        $this->get(route('projects.create'))->assertRedirect('login');
        $this->post(route('projects.store'))->assertRedirect('login');
        $this->get(route('projects.show', $project))->assertRedirect('login');
        $this->get(route('projects.edit', $project))->assertRedirect('login');
        $this->put(route('projects.update', $project))->assertRedirect('login');
        $this->delete(route('projects.destroy', $project))->assertRedirect('login');
    }

    /**
     * index: 認証済みユーザーはプロジェクト一覧ページを表示できる
     */
    public function test_index_displays_view_for_authenticated_user()
    {
        // このテストでは、ポリシーが 'viewAny' を許可することを前提とします。
        // 必要に応じて、特定のロールや権限をユーザーに付与する処理を追加してください。
        Project::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->get(route('projects.index'));

        $response->assertStatus(200);
        $response->assertViewIs('projects.index');
        $response->assertViewHas('projects');
    }

    /**
     * create: 認証済みユーザーはプロジェクト作成ページを表示できる
     */
    public function test_create_displays_view_for_authenticated_user()
    {
        $response = $this->actingAs($this->user)->get(route('projects.create'));

        $response->assertStatus(200);
        $response->assertViewIs('projects.create');
        $response->assertViewHas('customFormFields');
    }

    /**
     * store: 新しいプロジェクトを正常に作成できる
     */
    public function test_store_successfully_creates_new_project()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $postData = [
            'title' => 'New Awesome Project',
            'series_title' => 'Awesome Series',
            'client_name' => 'Test Client',
            'description' => 'This is a test project.',
            'start_date' => '2025-07-01',
            'end_date' => '2025-08-01',
            'color' => '#ff0000',
            'budget' => 500000,
            'target_cost' => 400000,
            'target_material_cost' => 250000,
            'target_labor_cost_rate' => 3000,
            'attributes' => [
                'text_field' => 'Some text value',
                'file_field' => [UploadedFile::fake()->image('photo1.jpg'), UploadedFile::fake()->create('document.pdf')],
            ],
        ];

        // グローバルフォーム定義を作成
        FormFieldDefinition::factory()->create(['name' => 'text_field', 'type' => 'text', 'label' => 'Text Field']);
        FormFieldDefinition::factory()->create(['name' => 'file_field', 'type' => 'file_multiple', 'label' => 'File Field']);


        $response = $this->post(route('projects.store'), $postData);

        $this->assertDatabaseHas('projects', ['title' => 'New Awesome Project', 'budget' => 500000]);
        $project = Project::first();
        $this->assertCount(2, $project->attributes['file_field']);
        Storage::disk('public')->assertExists($project->attributes['file_field'][0]['path']);
        Storage::disk('public')->assertExists($project->attributes['file_field'][1]['path']);

        // アクティビティログの確認
        $this->assertDatabaseHas('activity_log', [
            'description' => "案件「{$project->title}」が作成されました",
            'subject_type' => Project::class,
            'subject_id' => $project->id,
            'causer_id' => $this->user->id,
        ]);

        $response->assertRedirect(route('projects.show', $project));
    }

    /**
     * store: バリデーションが機能することを確認
     */
    public function test_store_fails_with_invalid_data()
    {
        $this->actingAs($this->user)
            ->post(route('projects.store'), ['title' => '']) // titleは必須
            ->assertSessionHasErrors('title');

        $this->actingAs($this->user)
            ->post(route('projects.store'), [
                'title' => 'Valid Title',
                'start_date' => '2025-08-01',
                'end_date' => '2025-07-01', // 終了日が開始日より前
            ])
            ->assertSessionHasErrors('end_date');

        $this->actingAs($this->user)
            ->post(route('projects.store'), [
                'title' => 'Valid Title',
                'color' => '#ff0000',
                'budget' => 10000,
                'target_cost' => 20000, // 予算が総売上を超えている
            ])
            ->assertSessionHasErrors('target_cost');
    }

    /**
     * store: 外部申請IDがある場合にステータスを更新する
     */
    public function test_store_updates_external_submission_status()
    {
        $submission = ExternalProjectSubmission::factory()->create(['status' => 'new']);

        $response = $this->actingAs($this->user)->post(route('projects.store'), [
            'title' => 'Project from Submission',
            'color' => '#ffffff',
            'external_submission_id_on_creation' => $submission->id,
        ]);

        $this->assertDatabaseHas('external_project_submissions', [
            'id' => $submission->id,
            'status' => 'processed',
            'processed_by_user_id' => $this->user->id,
        ]);
    }


    /**
     * show: プロジェクト詳細ページを正常に表示できる
     */
    public function test_show_displays_project_details()
    {
        $project = Project::factory()
            ->has(Task::factory()->count(3))
            ->create();

        $response = $this->actingAs($this->user)->get(route('projects.show', $project));

        $response->assertStatus(200);
        $response->assertViewIs('projects.show');
        $response->assertViewHas('project', function ($viewProject) use ($project) {
            return $viewProject->id === $project->id;
        });
        $response->assertSee($project->title);
    }

    /**
     * show: AjaxリクエストでタスクテーブルのHTMLを返す
     */
    public function test_show_returns_task_table_html_for_ajax_request()
    {
        $project = Project::factory()->has(Task::factory()->count(5), 'tasksWithoutCharacter')->create();
        $task = $project->tasksWithoutCharacter()->first();
        $task->update(['status' => 'completed']);

        // 完了済みを非表示にするリクエスト
        $response = $this->actingAs($this->user)->get(
            route('projects.show', ['project' => $project, 'hide_completed' => 'true']),
            ['X-Requested-With' => 'XMLHttpRequest']
        );

        $response->assertStatus(200);
        $response->assertJsonFragment(['html' => true]);
        // レスポンスのHTMLに完了済タスクが含まれていないことをアサート (簡易チェック)
        $jsonResponse = $response->json();
        $this->assertStringDoesNotContainString($task->name, $jsonResponse['html']);
    }

    /**
     * update: プロジェクト情報を正常に更新できる
     */
    public function test_update_successfully_modifies_project()
    {
        $project = Project::factory()->create(['title' => 'Old Title']);

        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated description.',
        ];

        $response = $this->actingAs($this->user)->put(route('projects.update', $project), $updateData);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'title' => 'Updated Title',
            'description' => 'Updated description.',
        ]);

        // アクティビティログの確認
        $this->assertDatabaseHas('activity_log', [
            'description' => "案件「Updated Title」が更新されました",
            'subject_type' => Project::class,
            'subject_id' => $project->id,
        ]);

        $response->assertRedirect(route('projects.show', $project));
    }

    /**
     * update: ファイルの追加と削除が正常に動作する
     */
    public function test_update_handles_file_addition_and_deletion()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        // 既存ファイルを持つプロジェクトを作成
        $existingFilePath = UploadedFile::fake()->create('existing_file.txt')->store('project_files/1/files', 'public');
        $fieldDefinitions = [
            ['name' => 'files', 'type' => 'file_multiple', 'label' => 'Files'],
        ];
        $project = Project::factory()->create([
            'form_definitions' => $fieldDefinitions,
            'attributes' => ['files' => [['path' => $existingFilePath, 'original_name' => 'existing_file.txt']]]
        ]);


        $updateData = [
            // 既存ファイルを削除する
            'attributes' => [
                'files_delete' => [$existingFilePath],
            ],
            // 新しいファイルを追加する
            // 注意: `attributes.files` のキー形式は、HTMLフォームのname属性に合わせる必要があります
            // ProjectControllerでは `attributes.{$fieldName}` で処理しているので、以下のようになります
        ];

        // 新しいファイルをリクエストに追加
        $newFile = UploadedFile::fake()->image('new_image.jpg');

        $response = $this->put(route('projects.update', $project), array_merge($updateData, ['attributes' => ['files' => [$newFile]]]));

        $project->refresh();
        $projectAttributes = $project->attributes;

        // 既存ファイルが削除されたことを確認
        Storage::disk('public')->assertMissing($existingFilePath);
        $this->assertFalse(collect($projectAttributes['files'])->contains('path', $existingFilePath));

        // 新しいファイルが追加されたことを確認
        $newFilePath = collect($projectAttributes['files'])->first()['path'];
        Storage::disk('public')->assertExists($newFilePath);
        $this->assertTrue(collect($projectAttributes['files'])->contains('original_name', 'new_image.jpg'));

        $response->assertRedirect(route('projects.show', $project));
    }


    /**
     * destroy: プロジェクトと関連データを削除できる
     */
    public function test_destroy_deletes_project_and_associated_data()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $filePath = UploadedFile::fake()->create('file_to_delete.txt')->store('project_files/1/files', 'public');
        $project = Project::factory()->create([
            'form_definitions' => [['name' => 'files', 'type' => 'file_multiple', 'label' => 'Files']],
            'attributes' => ['files' => [['path' => $filePath, 'original_name' => 'file_to_delete.txt']]]
        ]);
        $task = Task::factory()->create(['project_id' => $project->id]);
        $cost = Cost::factory()->create(['project_id' => $project->id]);

        $response = $this->delete(route('projects.destroy', $project));

        // ソフトデリートを使用している場合は assertSoftDeleted を使用
        // $this->assertSoftDeleted($project);

        // ソフトデリートを使用していない場合は assertDatabaseMissing を使用
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
        $this->assertDatabaseMissing('costs', ['id' => $cost->id]);
        Storage::disk('public')->assertMissing($filePath);

        $response->assertRedirect(route('projects.index'));
        $response->assertSessionHas('success', '案件が削除されました。');
    }

    /**
     * 各フラグ更新APIが正常に動作する
     */
    public function test_flag_update_apis_work_correctly()
    {
        $project = Project::factory()->create([
            'delivery_flag' => '0',
            'payment_flag' => 'Pending',
            'status' => 'not_started',
        ]);

        // delivery_flag
        $this->actingAs($this->user)
            ->putJson(route('projects.updateDeliveryFlag', $project), ['delivery_flag' => '1'])
            ->assertStatus(200)
            ->assertJson(['success' => true, 'delivery_flag' => '1']);
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'delivery_flag' => '1']);

        // payment_flag
        $this->actingAs($this->user)
            ->putJson(route('projects.updatePaymentFlag', $project), ['payment_flag' => 'Completed'])
            ->assertStatus(200)
            ->assertJson(['success' => true, 'payment_flag' => 'Completed']);
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'payment_flag' => 'Completed']);

        // status
        $this->actingAs($this->user)
            ->putJson(route('projects.updateStatus', $project), ['status' => 'in_progress'])
            ->assertStatus(200)
            ->assertJson(['success' => true, 'new_status' => 'in_progress']);
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => 'in_progress']);
    }

    /**
     * storeCompletionFolder: 完成データ用のフォルダを正常に作成できる
     */
    public function test_store_completion_folder_creates_folder_task()
    {
        $project = Project::factory()->create();
        // 親フォルダを事前に作成
        $masterFolder = Task::factory()->create([
            'project_id' => $project->id,
            'name' => '_project_completion_data_',
            'is_folder' => true
        ]);

        $folderName = 'Final Photos';
        $response = $this->actingAs($this->user)->post(route('projects.storeCompletionFolder', $project), [
            'name' => $folderName,
            'parent_id' => $masterFolder->id,
        ]);

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'parent_id' => $masterFolder->id,
            'name' => $folderName,
            'is_folder' => true,
        ]);
        $response->assertRedirect(route('projects.show', $project));
    }
}
