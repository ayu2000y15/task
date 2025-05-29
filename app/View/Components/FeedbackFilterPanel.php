<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection; // Collectionの型ヒント用

class FeedbackFilterPanel extends Component
{
    public string $action;
    public array $filters;
    public Collection|array $feedbackCategories; // カテゴリの選択肢
    public array $statusOptions;       // ステータスの選択肢

    /**
     * Create a new component instance.
     *
     * @param string $action フォームの送信先URL
     * @param array $filters 現在適用されているフィルターの値
     * @param \Illuminate\Support\Collection|array $feedbackCategories カテゴリの選択肢
     * @param array $statusOptions ステータスの選択肢
     */
    public function __construct(
        string $action,
        array $filters,
        $feedbackCategories,
        array $statusOptions
    ) {
        $this->action = $action;
        $this->filters = $filters;
        $this->feedbackCategories = $feedbackCategories;
        $this->statusOptions = $statusOptions;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.feedback-filter-panel');
    }
}
