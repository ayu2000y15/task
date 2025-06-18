<?php

namespace App\Observers;

use App\Models\Project;

class ProjectObserver
{
    /**
     * Handle the Project "saving" event.
     *
     * This method is called before a project is saved (created or updated).
     * It automatically updates the project's status based on delivery and payment flags.
     *
     * @param  \App\Models\Project  $project
     * @return void
     */
    public function saving(Project $project): void
    {
        // この自動ロジックで上書きすべきでないステータスを定義
        $protectedStatuses = ['cancelled'];

        // 現在のステータスが保護対象で、かつ今回の保存操作でステータス自体が変更されていない場合は、何もしない
        if (in_array($project->getOriginal('status'), $protectedStatuses) && !$project->isDirty('status')) {
            return;
        }
        // もし、今回の保存でステータスが保護対象のステータスに明示的に変更される場合は、その変更を優先する
        if ($project->isDirty('status') && in_array($project->status, $protectedStatuses)) {
            return;
        }

        // プロジェクトが「完了」とみなされる条件
        $isCompletionCriteriaMet = ($project->delivery_flag === '1' && $project->payment_flag === 'Completed');

        if ($isCompletionCriteriaMet) {
            // 条件を満たせば「完了」に設定
            $project->status = 'completed';
        } else {
            // 条件を満たさない場合
            // 現在のステータスが「完了」だった場合は「進行中」に戻す
            if ($project->getOriginal('status') === 'completed') {
                $project->status = 'in_progress';
            }
            // 現在のステータスが「未着手」で、かつ納品フラグまたは支払いフラグが今回の保存で変更された場合、
            // 「進行中」にする (何らかのアクションがあったとみなす)
            elseif ($project->status === 'not_started' && ($project->isDirty('delivery_flag') || $project->isDirty('payment_flag'))) {
                $project->status = 'in_progress';
            }
            // 現在のステータスが「一時停止中」などの他のアクティブな状態であれば、「進行中」にする
            // (ただし、「未着手」でフラグ変更なしの場合はそのまま「未着手」を維持)
            elseif (!in_array($project->status, ['not_started', 'cancelled'])) {
                $project->status = 'in_progress';
            }
            // それ以外の場合（例：'not_started'でフラグ変更なし、または'cancelled'）は、ステータスを現状維持
            // (cancelledは最初のif文で保護されている)
        }
    }

    /**
     * Handle the Project "created" event.
     *
     * @param  \App\Models\Project  $project
     * @return void
     */
    public function created(Project $project): void
    {
        // savingイベントで処理されるため、通常はここでは不要
    }

    // updated, deleted, restored, forceDeleted メソッドは変更なし
    // ... (他のオブザーバーメソッド) ...
    /**
     * Handle the Project "updated" event.
     *
     * @param  \App\Models\Project  $project
     * @return void
     */
    public function updated(Project $project): void
    {
        //
    }

    /**
     * Handle the Project "deleted" event.
     *
     * @param  \App\Models\Project  $project
     * @return void
     */
    public function deleted(Project $project): void
    {
        //
    }

    /**
     * Handle the Project "restored" event.
     *
     * @param  \App\Models\Project  $project
     * @return void
     */
    public function restored(Project $project): void
    {
        //
    }

    /**
     * Handle the Project "force deleted" event.
     *
     * @param  \App\Models\Project  $project
     * @return void
     */
    public function forceDeleted(Project $project): void
    {
        //
    }
}
