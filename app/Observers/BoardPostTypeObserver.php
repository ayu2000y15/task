<?php

namespace App\Observers;

use App\Models\BoardPostType;
use App\Models\FormFieldCategory;
use App\Models\FormFieldDefinition;

class BoardPostTypeObserver
{
    /**
     * Handle the BoardPostType "created" event.
     */
    public function created(BoardPostType $boardPostType): void
    {
        // お知らせタイプの場合はカテゴリを作成しない
        if ($boardPostType->name === 'announcement') {
            return;
        }

        // 投稿タイプが作成されたら、対応するカスタム項目カテゴリを作成
        FormFieldCategory::firstOrCreate([
            'name' => $boardPostType->name,
        ], [
            'display_name' => $boardPostType->display_name,
            'description' => "投稿タイプ「{$boardPostType->display_name}」のカスタム項目カテゴリ",
            'order' => FormFieldCategory::max('order') + 1 ?? 1,
            'is_enabled' => true,
        ]);
    }

    /**
     * Handle the BoardPostType "updated" event.
     */
    public function updated(BoardPostType $boardPostType): void
    {
        // お知らせタイプの場合はカテゴリを操作しない
        if ($boardPostType->name === 'announcement') {
            return;
        }

        // 投稿タイプが更新されたら、対応するカスタム項目カテゴリも更新
        $category = FormFieldCategory::where('name', $boardPostType->getOriginal('name'))->first();

        if ($category) {
            $category->update([
                'name' => $boardPostType->name,
                'display_name' => $boardPostType->display_name,
            ]);

            // nameが変更された場合、FormFieldDefinitionのcategoryも更新
            if ($boardPostType->getOriginal('name') !== $boardPostType->name) {
                FormFieldDefinition::where('category', $boardPostType->getOriginal('name'))
                    ->update(['category' => $boardPostType->name]);
            }
        }
    }

    /**
     * Handle the BoardPostType "deleted" event.
     */
    public function deleted(BoardPostType $boardPostType): void
    {
        // お知らせタイプの場合はカテゴリを操作しない
        if ($boardPostType->name === 'announcement') {
            return;
        }

        // 投稿タイプが削除されたら、対応するカスタム項目カテゴリの処理
        $category = FormFieldCategory::where('name', $boardPostType->name)->first();

        if ($category) {
            // このカテゴリを使用しているFormFieldDefinitionがあるかチェック
            $hasFormFields = FormFieldDefinition::where('category', $boardPostType->name)->exists();

            if (!$hasFormFields) {
                // カスタム項目が存在しない場合はカテゴリも削除
                $category->delete();
            } else {
                // カスタム項目が存在する場合は無効にする
                $category->update(['is_enabled' => false]);
            }
        }
    }

    /**
     * Handle the BoardPostType "restored" event.
     */
    public function restored(BoardPostType $boardPostType): void
    {
        // お知らせタイプの場合はカテゴリを操作しない
        if ($boardPostType->name === 'announcement') {
            return;
        }

        // 投稿タイプが復元されたら、対応するカスタム項目カテゴリも有効にする
        $category = FormFieldCategory::where('name', $boardPostType->name)->first();

        if ($category) {
            $category->update(['is_enabled' => true]);
        } else {
            // カテゴリが存在しない場合は新規作成
            $this->created($boardPostType);
        }
    }
}
