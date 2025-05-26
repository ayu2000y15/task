<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Contracts\View\View;

class FilterPanel extends Component
{
    /**
     * @param string $action The form action URL.
     * @param array $filters The current filter values.
     * @param \Illuminate\Database\Eloquent\Collection|array $allProjects List of all projects for the dropdown.
     * @param \Illuminate\Database\Eloquent\Collection|array $allCharacters List of characters for the dropdown.
     * @param \Illuminate\Support\Collection|array $allAssignees List of all assignees for the dropdown.
     * @param array $statusOptions List of status options for the dropdown.
     * @param bool $showDueDateFilter Whether to show the due date filter.
     * @param bool $showDateRangeFilter Whether to show the date range filter.
     */
    public function __construct(
        public string $action,
        public array $filters,
        public $allProjects,
        public $allCharacters, // ★ 変更後: エラーメッセージに合わせる
        public $allAssignees,
        public array $statusOptions,
        public bool $showDueDateFilter = false,
        public bool $showDateRangeFilter = false
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.filter-panel');
    }
}
