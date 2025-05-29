<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class FeedbackFilterPanel extends Component
{
    public string $action;
    public array $filters;
    public Collection|array $feedbackCategories;
    public array $statusOptions;
    public array $priorityOptions; // ★ public プロパティとして定義

    public function __construct(
        string $action,
        array $filters,
        $feedbackCategories,
        array $statusOptions,
        array $priorityOptions // ★ コンストラクタの引数
    ) {
        $this->action = $action;
        $this->filters = $filters;
        $this->feedbackCategories = $feedbackCategories;
        $this->statusOptions = $statusOptions;
        $this->priorityOptions = $priorityOptions; // ★ プロパティに代入
    }

    public function render(): View
    {
        return view('components.feedback-filter-panel');
    }
}
