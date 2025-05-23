<div class="collapse {{ array_filter($filters) ? 'show' : '' }}" id="filterPanel">
    <div class="filter-panel mb-4">
        <div class="filter-close" id="closeFilterBtn" data-bs-toggle="collapse" data-bs-target="#filterPanel"
            aria-label="フィルターを閉じる" style="cursor: pointer;">
            <i class="fas fa-times"></i>
        </div>
        <form action="{{ $action }}" method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="project_id" class="form-label">プロジェクト</label>
                <select class="form-select" id="project_id" name="project_id">
                    <option value="">すべて</option>
                    @foreach($allProjects as $project)
                        <option value="{{ $project->id }}" @if(isset($filters['project_id']) && $filters['project_id'] == $project->id) selected @endif>
                            {{ $project->title }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="assignee" class="form-label">担当者</label>
                <select class="form-select" id="assignee" name="assignee">
                    <option value="">すべて</option>
                    @foreach($allAssignees as $assignee)
                        <option value="{{ $assignee }}" @if(isset($filters['assignee']) && $filters['assignee'] == $assignee)
                        selected @endif>
                            {{ $assignee }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">ステータス</label>
                <select class="form-select" id="status" name="status">
                    <option value="">すべて</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @if(isset($filters['status']) && $filters['status'] == $value) selected
                        @endif>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="search" class="form-label">タスク名検索</label>
                <input type="text" class="form-control" id="search" name="search"
                    value="{{ $filters['search'] ?? '' }}">
            </div>

            @if($showDueDateFilter)
                <div class="col-md-3">
                    <label for="due_date" class="form-label">期限</label>
                    <select class="form-select" id="due_date" name="due_date">
                        <option value="">すべて</option>
                        <option value="overdue" @if(isset($filters['due_date']) && $filters['due_date'] == 'overdue') selected
                        @endif>期限切れ</option>
                        <option value="today" @if(isset($filters['due_date']) && $filters['due_date'] == 'today') selected
                        @endif>今日</option>
                        <option value="tomorrow" @if(isset($filters['due_date']) && $filters['due_date'] == 'tomorrow')
                        selected @endif>明日</option>
                        <option value="this_week" @if(isset($filters['due_date']) && $filters['due_date'] == 'this_week')
                        selected @endif>今週</option>
                        <option value="next_week" @if(isset($filters['due_date']) && $filters['due_date'] == 'next_week')
                        selected @endif>来週</option>
                    </select>
                </div>
            @endif

            @if($showDateRangeFilter)
                <div class="col-md-3">
                    <label for="start_date" class="form-label">表示開始日</label>
                    <input type="date" class="form-control" id="start_date" name="start_date"
                        value="{{ $filters['start_date'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">表示終了日</label>
                    <input type="date" class="form-control" id="end_date" name="end_date"
                        value="{{ $filters['end_date'] ?? '' }}">
                </div>
            @endif

            <div class="col-md-12 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">フィルター適用</button>
                <a href="{{ $action }}" class="btn btn-secondary">リセット</a>
            </div>
        </form>
    </div>
</div>

{{-- フィルターパネルを閉じるボタンのためのScriptは不要になったため削除 --}}
{{--
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof filterPanelInitialized === 'undefined') {
            const closeFilterBtn = document.getElementById('closeFilterBtn');
            if (closeFilterBtn) {
                closeFilterBtn.addEventListener('click', function () {
                    const filterPanel = document.getElementById('filterPanel');
                    const bsCollapse = new bootstrap.Collapse(filterPanel, {
                        toggle: false
                    });
                    bsCollapse.hide();
                });
            }
            window.filterPanelInitialized = true;
        }
    });
</script>
@endpush
--}}