<?php

namespace App\Services;

use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class TaskService
{
    /**
     * フィルター条件に基づいて工程のクエリを構築する
     *
     * @param array $filters
     * @return Builder
     */
    public function buildFilteredQuery(array $filters): Builder
    {
        $query = Task::query();

        $this->applyProjectFilter($query, $filters['project_id'] ?? null);
        $this->applyAssigneeFilter($query, $filters['assignee_id'] ?? null);
        $this->applyCharacterFilter($query, $filters['character_id'] ?? null); // ★ 追加
        $this->applyStatusFilter($query, $filters['status'] ?? null);
        $this->applySearchFilter($query, $filters['search'] ?? null);
        $this->applyDueDateFilter($query, $filters['due_date'] ?? null);
        $this->applyDateRangeFilter($query, $filters['start_date'] ?? null, $filters['end_date'] ?? null);

        return $query;
    }

    /**
     * 衣装案件IDでフィルタリング
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
    public function applyAssigneeFilter(Builder|Relation $query, ?string $assigneeId): void
    {
        if ($assigneeId) {
            // 'assignees'リレーションが存在し、その中のユーザーIDが一致するタスクを絞り込む
            $query->whereHas('assignees', function ($q) use ($assigneeId) {
                $q->where('users.id', $assigneeId);
            });
        }
    }

    /**
     * キャラクターでフィルタリング
     */
    public function applyCharacterFilter(Builder|Relation $query, ?string $characterId): void
    {
        if ($characterId) {
            // 'none' はキャラクターに紐づかないタスクを示す特別な値として扱う
            $query->where($characterId === 'none' ? 'character_id' : 'character_id', $characterId === 'none' ? null : $characterId);
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
     * 工程名で検索
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
