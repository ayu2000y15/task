<?php

namespace App\Services;

use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class TaskService
{
    /**
     * フィルター条件に基づいてタスクのクエリを構築する
     *
     * @param array $filters
     * @return Builder
     */
    public function buildFilteredQuery(array $filters): Builder
    {
        $query = Task::query();

        $this->applyProjectFilter($query, $filters['project_id'] ?? null);
        $this->applyAssigneeFilter($query, $filters['assignee'] ?? null);
        $this->applyStatusFilter($query, $filters['status'] ?? null);
        $this->applySearchFilter($query, $filters['search'] ?? null);
        $this->applyDueDateFilter($query, $filters['due_date'] ?? null);
        $this->applyDateRangeFilter($query, $filters['start_date'] ?? null, $filters['end_date'] ?? null);

        return $query;
    }

    /**
     * プロジェクトIDでフィルタリング
     */
    public function applyProjectFilter(Builder|Relation $query, ?string $projectId): void
    {
        if ($projectId) {
            $query->where('project_id', $projectId);
        }
    }

    /**
     * 担当者でフィルタリング
     */
    public function applyAssigneeFilter(Builder|Relation $query, ?string $assignee): void
    {
        if ($assignee) {
            $query->where('assignee', $assignee);
        }
    }

    /**
     * ステータスでフィルタリング
     */
    public function applyStatusFilter(Builder|Relation $query, ?string $status): void
    {
        if ($status) {
            $query->where('status', $status);
        }
    }

    /**
     * タスク名で検索
     */
    public function applySearchFilter(Builder|Relation $query, ?string $search): void
    {
        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }
    }

    /**
     * 期限でフィルタリング
     */
    public function applyDueDateFilter(Builder|Relation $query, ?string $dueDate): void
    {
        if ($dueDate) {
            $now = Carbon::now()->startOfDay();

            match ($dueDate) {
                'overdue' => $query->where('end_date', '<', $now)->whereNotIn('status', ['completed', 'cancelled']),
                'today' => $query->whereDate('end_date', $now),
                'tomorrow' => $query->whereDate('end_date', $now->copy()->addDay()),
                'this_week' => $query->whereBetween('end_date', [$now->copy()->addDay(), $now->copy()->endOfWeek()]),
                'next_week' => $query->whereBetween('end_date', [$now->copy()->startOfWeek()->addWeek(), $now->copy()->endOfWeek()->addWeek()]),
                default => null,
            };
        }
    }

    /**
     * 日付範囲でフィルタリング
     */
    public function applyDateRangeFilter(Builder|Relation $query, ?string $startDate, ?string $endDate): void
    {
        if ($startDate) {
            $query->where('end_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('start_date', '<=', $endDate);
        }
    }
}
