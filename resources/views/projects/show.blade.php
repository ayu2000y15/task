@extends('layouts.app')

@section('title', '案件詳細 - ' . $project->title)

@section('styles')
<style>
    .character-accordion-button::after { /* アコーディオンの矢印を少し小さく */
        flex-shrink: 0;
        width: 0.9rem;
        height: 0.9rem;
        margin-left: auto;
        content: "";
        background-image: var(--bs-accordion-btn-icon);
        background-repeat: no-repeat;
        background-size: 0.9rem;
        transition: var(--bs-accordion-btn-icon-transition);
    }
    .character-accordion-button:not(.collapsed)::after {
        background-image: var(--bs-accordion-btn-active-icon);
        transform: var(--bs-accordion-btn-icon-transform);
    }
</style>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>{{ $project->title }}</h1>
        <div>
            <a href="{{ route('projects.tasks.create', $project) }}" class="btn btn-primary me-2">
                <i class="fas fa-plus"></i> 工程追加
            </a>
            @can('update', $project)
            <a href="{{ route('projects.edit', $project) }}" class="btn btn-warning me-2">
                <i class="fas fa-edit"></i> 案件編集
            </a>
            @endcan
            <a href="{{ route('gantt.index', ['project_id' => $project->id]) }}" class="btn btn-info">
                <i class="fas fa-chart-gantt"></i> ガントチャート
            </a>
        </div>
    </div>


    <div class="row">
        <div class="col-lg-4">
            {{-- 案件情報カード --}}
            <div class="card mb-4">
                <div class="card-header" style="background-color: {{ $project->color }}; color: white;">
                    <h5 class="mb-0">案件情報</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6 mb-2"><strong>作品名:</strong><p class="mb-0">{{ $project->series_title ?? '-' }}</p></div>
                        <div class="col-sm-6 mb-2"><strong>依頼主名:</strong><p class="mb-0">{{ $project->client_name ?? '-' }}</p></div>
                    </div>
                    <div class="mb-2">
                        <strong>期間:</strong>
                        <p class="mb-0">{{ $project->start_date->format('Y年m月d日') }} 〜 {{ $project->end_date->format('Y年m月d日') }}</p>
                    </div>
                    <div class="mb-2">
                        <strong>備考:</strong>
                        <p class="mb-0">{{ $project->description ?? '-' }}</p>
                    </div>
                    <div class="mb-2">
                        <strong>ステータス (工程進捗):</strong>
                        @php
                            $totalTasks = $project->tasks()->where('is_folder', false)->where('is_milestone', false)->count();
                            $completedTasks = $project->tasks()->where('is_folder', false)->where('is_milestone', false)->where('status', 'completed')->count();
                            $progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
                        @endphp
                        <div class="progress mt-1">
                            <div class="progress-bar" role="progressbar"
                                style="width: {{ $progress }}%; background-color: {{ $project->color }};"
                                aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">{{ $progress }}%</div>
                        </div>
                    </div>
                    <div>
                        <strong>工程数:</strong>
                        <p class="mb-0">{{ $totalTasks }}工程 (完了: {{ $completedTasks }}工程)</p>
                    </div>
                </div>
            </div>

            {{-- 統計カード --}}
            <div class="card mb-4">
                 <div class="card-header"><h5 class="mb-0">統計</h5></div>
                <div class="card-body">
                    {{-- (既存の統計カードの内容は変更なし) --}}
                    <div class="mb-3">
                        <strong>ステータス別工程数:</strong>
                        <ul class="list-group mt-2">
                            <li class="list-group-item d-flex justify-content-between align-items-center">未着手 <span class="badge bg-secondary rounded-pill">{{ $project->tasks->where('status', 'not_started')->count() }}</span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">進行中 <span class="badge bg-primary rounded-pill">{{ $project->tasks->where('status', 'in_progress')->count() }}</span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">完了 <span class="badge bg-success rounded-pill">{{ $project->tasks->where('status', 'completed')->count() }}</span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">保留中 <span class="badge bg-warning rounded-pill">{{ $project->tasks->where('status', 'on_hold')->count() }}</span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">キャンセル <span class="badge bg-danger rounded-pill">{{ $project->tasks->where('status', 'cancelled')->count() }}</span></li>
                        </ul>
                    </div>
                    <div>
                        <strong>タイプ別工程数:</strong>
                        <ul class="list-group mt-2">
                            <li class="list-group-item d-flex justify-content-between align-items-center">通常工程 <span class="badge bg-primary rounded-pill">{{ $project->tasks->where('is_milestone', false)->where('is_folder', false)->count() }}</span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">重要納期 <span class="badge bg-info rounded-pill">{{ $project->tasks->where('is_milestone', true)->count() }}</span></li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">パーツ(フォルダ) <span class="badge bg-secondary rounded-pill">{{ $project->tasks->where('is_folder', true)->count() }}</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
             {{-- キャラクター管理カード --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">登場キャラクター</h5>
                </div>
                <div class="card-body">
                    @can('update', $project) {{-- キャラクター追加フォームは案件編集権限で制御 --}}
                    <form action="{{ route('projects.characters.store', $project) }}" method="POST" class="mb-3 p-3 border rounded">
                        @csrf
                        <div class="row g-2">
                            <div class="col-md">
                                <label for="character_name_add" class="form-label visually-hidden">新規キャラクター名</label>
                                <input type="text" name="name" id="character_name_add" class="form-control form-control-sm @error('name') is-invalid @enderror" placeholder="新規キャラクター名" required value="{{ old('name') }}">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md">
                                <label for="character_description_add" class="form-label visually-hidden">キャラクター備考</label>
                                <textarea name="description" id="character_description_add" class="form-control form-control-sm @error('description') is-invalid @enderror" placeholder="キャラクター備考 (任意)" rows="1">{{ old('description') }}</textarea>
                                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-auto">
                                <button type="submit" class="btn btn-sm btn-primary w-100">キャラクター追加</button>
                            </div>
                        </div>
                    </form>
                    @endcan

                    @if($project->characters->isEmpty())
                        <p class="text-center text-muted">キャラクターが登録されていません。</p>
                    @else
                        <div class="accordion" id="charactersAccordion">
                            @foreach($project->characters as $idx => $character)
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingCharacter{{ $character->id }}">
                                    <button class="accordion-button @if($idx > 0) collapsed @endif character-accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCharacter{{ $character->id }}" aria-expanded="{{ $idx === 0 ? 'true' : 'false' }}" aria-controls="collapseCharacter{{ $character->id }}">
                                        <strong>{{ $character->name }}</strong>
                                        @can('update', $project)
                                        <span class="ms-auto"> <a href="{{ route('characters.edit', $character) }}" class="btn btn-xs btn-outline-primary py-0 px-1 me-1"><i class="fas fa-edit"></i></a>
                                            <form action="{{ route('characters.destroy', $character) }}" method="POST" class="d-inline" onsubmit="return confirm('このキャラクターを削除しますか？関連する採寸・材料・コストも全て削除されます。');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-xs btn-outline-danger py-0 px-1"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </span>
                                        @endcan
                                    </button>
                                </h2>
                                <div id="collapseCharacter{{ $character->id }}" class="accordion-collapse collapse @if($idx === 0) show @endif" aria-labelledby="headingCharacter{{ $character->id }}" data-bs-parent="#charactersAccordion">
                                    <div class="accordion-body">
                                        @if($character->description)
                                            <p class="mb-2"><small><strong>備考:</strong> {{ $character->description }}</small></p>
                                        @endif

                                        {{-- キャラクターごとの採寸・材料・コストはここにネスト --}}
                                        @can('update', $project) {{-- サブ機能の管理権限も案件編集権限で代用 --}}
                                        {{-- 採寸データ --}}
                                        <div class="card mb-3">
                                            <div class="card-header bg-light py-2"><h6 class="mb-0">採寸情報</h6></div>
                                            <div class="card-body p-2">
                                                <table class="table table-sm table-borderless mb-2">
                                                    <tbody>
                                                        @forelse($character->measurements as $measurement)
                                                            <tr>
                                                                <th>{{ $measurement->item }}</th>
                                                                <td>{{ $measurement->value }} {{ $measurement->unit }}</td>
                                                                <td class="text-end">
                                                                    <form action="{{ route('projects.characters.measurements.destroy', [$project, $character, $measurement]) }}" method="POST" onsubmit="return confirm('本当に削除しますか？');">
                                                                        @csrf @method('DELETE')
                                                                        <button type="submit" class="btn btn-xs btn-outline-danger py-0 px-1"><i class="fas fa-trash"></i></button>
                                                                    </form>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr><td colspan="3" class="text-center text-muted small">採寸データなし</td></tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="card-footer p-2">
                                                <form action="{{ route('projects.characters.measurements.store', [$project, $character]) }}" method="POST" class="row gx-2">
                                                    @csrf
                                                    <div class="col"><input type="text" name="item" class="form-control form-control-sm" placeholder="項目" required></div>
                                                    <div class="col"><input type="text" name="value" class="form-control form-control-sm" placeholder="数値" required></div>
                                                    <div class="col-auto"><select name="unit" class="form-select form-select-sm"><option value="cm">cm</option><option value="mm">mm</option></select></div>
                                                    <div class="col-auto"><button type="submit" class="btn btn-xs btn-outline-success">追加</button></div>
                                                </form>
                                            </div>
                                        </div>

                                        {{-- 材料リスト --}}
                                        <div class="card mb-3">
                                            <div class="card-header bg-light py-2"><h6 class="mb-0">材料リスト</h6></div>
                                            <div class="card-body p-2">
                                                <table class="table table-sm table-borderless mb-2">
                                                    <thead><tr><th>状態</th><th>材料名</th><th class="text-end">価格</th><th>必要量</th><th></th></tr></thead>
                                                    <tbody>
                                                        @forelse($character->materials as $material)
                                                            <tr>
                                                                <td><input type="checkbox" class="form-check-input material-status-check" data-url="{{ route('projects.characters.materials.update', [$project, $character, $material]) }}" {{ $material->status === '購入済' ? 'checked' : '' }}></td>
                                                                <td>{{ $material->name }}</td>
                                                                <td class="text-end">{{ !is_null($material->price) ? number_format($material->price) . '円' : '-' }}</td>
                                                                <td>{{ $material->quantity_needed }}</td>
                                                                <td class="text-end">
                                                                    <form action="{{ route('projects.characters.materials.destroy', [$project, $character, $material]) }}" method="POST" onsubmit="return confirm('本当に削除しますか？');">
                                                                        @csrf @method('DELETE')
                                                                        <button type="submit" class="btn btn-xs btn-outline-danger py-0 px-1"><i class="fas fa-trash"></i></button>
                                                                    </form>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr><td colspan="5" class="text-center text-muted small">材料なし</td></tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="card-footer p-2">
                                                <form action="{{ route('projects.characters.materials.store', [$project, $character]) }}" method="POST" class="row gx-2">
                                                    @csrf
                                                    <div class="col-5"><input type="text" name="name" class="form-control form-control-sm" placeholder="材料名" required></div>
                                                    <div class="col"><input type="number" name="price" class="form-control form-control-sm" placeholder="価格"></div>
                                                    <div class="col"><input type="text" name="quantity_needed" class="form-control form-control-sm" placeholder="必要量" required></div>
                                                    <div class="col-auto"><button type="submit" class="btn btn-xs btn-outline-success">追加</button></div>
                                                </form>
                                            </div>
                                        </div>

                                        {{-- コスト管理 --}}
                                        <div class="card">
                                            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0">コスト管理</h6>
                                                <span class="badge bg-dark">合計: {{ number_format($character->costs->sum('amount')) }} 円</span>
                                            </div>
                                            <div class="card-body p-2">
                                                <table class="table table-sm table-borderless mb-2">
                                                    <thead><tr><th>日付</th><th>内容</th><th>種別</th><th class="text-end">金額</th><th></th></tr></thead>
                                                    <tbody>
                                                        @forelse($character->costs as $cost)
                                                            <tr>
                                                                <td>{{ $cost->cost_date->format('y/m/d') }}</td>
                                                                <td>{{ $cost->item_description }}</td>
                                                                <td><span class="badge bg-info text-dark x-small">{{ $cost->type }}</span></td>
                                                                <td class="text-end">{{ number_format($cost->amount) }}円</td>
                                                                <td class="text-end">
                                                                    <form action="{{ route('projects.characters.costs.destroy', [$project, $character, $cost]) }}" method="POST" onsubmit="return confirm('本当に削除しますか？');">
                                                                        @csrf @method('DELETE')
                                                                        <button type="submit" class="btn btn-xs btn-outline-danger py-0 px-1"><i class="fas fa-trash"></i></button>
                                                                    </form>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr><td colspan="5" class="text-center text-muted small">コストなし</td></tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="card-footer p-2">
                                                <form action="{{ route('projects.characters.costs.store', [$project, $character]) }}" method="POST" class="row gx-2">
                                                    @csrf
                                                    <div class="col-4"><input type="text" name="item_description" class="form-control form-control-sm" placeholder="内容" required></div>
                                                    <div class="col"><input type="number" name="amount" class="form-control form-control-sm" placeholder="金額" required></div>
                                                    <div class="col-auto"><input type="date" name="cost_date" class="form-control form-control-sm" value="{{ today()->format('Y-m-d') }}" required></div>
                                                    <div class="col-auto"><select name="type" class="form-select form-select-sm"><option value="材料費">材料費</option><option value="作業費">作業費</option><option value="その他">その他</option></select></div>
                                                    <div class="col-auto"><button type="submit" class="btn btn-xs btn-outline-success">追加</button></div>
                                                </form>
                                            </div>
                                        </div>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>


            {{-- 工程一覧カード --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">工程一覧</h5>
                    @can('create', App\Models\Task::class) {{-- Task作成権限 --}}
                    <div class="btn-group">
                        <a href="{{ route('projects.tasks.create', $project) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> 工程追加
                        </a>
                        <button class="btn btn-sm btn-outline-secondary" id="toggleCompletedBtn">
                            <i class="fas fa-eye-slash"></i> 完了工程を隠す
                        </button>
                    </div>
                    @endcan
                </div>
                <div class="card-body p-0">
                    {{-- (既存の工程一覧テーブルは変更なし) --}}
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>工程名</th>
                                    <th>担当者</th>
                                    <th>期間</th>
                                    <th>工数</th>
                                    <th>進捗</th>
                                    <th>ステータス</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($project->tasks->isEmpty())
                                    <tr>
                                        <td colspan="7" class="text-center py-4">工程がありません</td>
                                    </tr>
                                @else
                                    @foreach($project->tasks->sortBy(function ($task) { return $task->start_date ?? '9999-12-31'; }) as $task)
                                        @php
                                            $rowClass = '';
                                            $now = \Carbon\Carbon::now()->startOfDay();
                                            $daysUntilDue = $task->end_date ? $now->diffInDays($task->end_date, false) : null;

                                            if ($task->status === 'completed' || $task->status === 'cancelled') {
                                                $rowClass = 'completed-task';
                                            } elseif (!$task->is_folder && !$task->is_milestone && $task->end_date && $task->end_date < $now) {
                                                $rowClass = 'task-overdue';
                                            } elseif (!$task->is_folder && !$task->is_milestone && $daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2) {
                                                $rowClass = 'task-due-soon';
                                            }
                                        @endphp
                                        <tr class="{{ $rowClass }}">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="task-icon me-2">
                                                        @if($task->is_milestone)
                                                            <i class="fas fa-flag text-danger"></i>
                                                        @elseif($task->is_folder)
                                                            <i class="fas fa-folder text-primary"></i>
                                                        @else
                                                            <i class="fas fa-tasks"></i>
                                                        @endif
                                                    </span>
                                                    <a href="{{ route('projects.tasks.edit', [$project, $task]) }}"
                                                        class="text-decoration-none">{{ $task->name }}</a>
                                                    @if(!$task->is_milestone && !$task->is_folder && $task->end_date && $task->end_date < $now && $task->status !== 'completed' && $task->status !== 'cancelled')
                                                        <span class="ms-2 badge bg-danger">期限切れ</span>
                                                    @elseif(!$task->is_milestone && !$task->is_folder && $daysUntilDue !== null && $daysUntilDue >= 0 && $daysUntilDue <= 2 && $task->status !== 'completed' && $task->status !== 'cancelled')
                                                        <span class="ms-2 badge bg-warning text-dark">あと{{ $daysUntilDue }}日</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>{{ $task->assignee ?? '-' }}</td>
                                            <td>
                                                {{ $task->start_date ? $task->start_date->format('Y/m/d') : '-' }}
                                                〜
                                                {{ $task->end_date ? $task->end_date->format('Y/m/d') : '-' }}
                                            </td>
                                            <td>
                                                {{ $task->is_folder ? '-' : ($task->duration ? $task->duration . '日' : '-') }}
                                            </td>
                                            <td>
                                                @if(!$task->is_folder && !$task->is_milestone)
                                                    <div class="progress" style="height: 10px;">
                                                        <div class="progress-bar" role="progressbar"
                                                            style="width: {{ $task->progress }}%; background-color: {{ $project->color }};"
                                                            aria-valuenow="{{ $task->progress }}" aria-valuemin="0" aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                    <small>{{ $task->progress }}%</small>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                @if($task->is_folder)
                                                    -
                                                @else
                                                    @php
                                                        $statusClass = ''; $statusLabel = '';
                                                        switch ($task->status) {
                                                            case 'not_started': $statusClass = 'secondary'; $statusLabel = '未着手'; break;
                                                            case 'in_progress': $statusClass = 'primary'; $statusLabel = '進行中'; break;
                                                            case 'completed': $statusClass = 'success'; $statusLabel = '完了'; break;
                                                            case 'on_hold': $statusClass = 'warning'; $statusLabel = '保留中'; break;
                                                            case 'cancelled': $statusClass = 'danger'; $statusLabel = 'キャンセル'; break;
                                                            default: $statusClass = 'light'; $statusLabel = $task->status ?? '-'; break;
                                                        }
                                                    @endphp
                                                    <span class="badge bg-{{ $statusClass }}">{{ $statusLabel }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                 @can('update', $task) {{-- Task更新権限 --}}
                                                <a href="{{ route('projects.tasks.edit', [$project, $task]) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                                @endcan
                                                @can('delete', $task) {{-- Task削除権限 --}}
                                                <form action="{{ route('projects.tasks.destroy', [$project, $task]) }}" method="POST" class="d-inline" onsubmit="return confirm('本当に削除しますか？');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                                </form>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 完了タスクの表示/非表示切り替え
            const toggleCompletedBtn = document.getElementById('toggleCompletedBtn');
            if (toggleCompletedBtn) {
                let completedTasksHidden = false;
                toggleCompletedBtn.addEventListener('click', function () {
                    const taskRows = document.querySelectorAll('.card-body table tbody tr'); // より広い範囲のタスク行を取得
                    taskRows.forEach(taskRow => {
                        if (taskRow.classList.contains('completed-task')) {
                             taskRow.style.display = completedTasksHidden ? '' : 'none';
                        }
                    });
                    completedTasksHidden = !completedTasksHidden;
                    this.innerHTML = completedTasksHidden ? '<i class="fas fa-eye"></i> 完了工程を表示' : '<i class="fas fa-eye-slash"></i> 完了工程を隠す';
                });
            }

            // 材料ステータス更新のイベントリスナー (複数キャラクター対応のため、動的に要素を取得)
            document.body.addEventListener('change', function(event) {
                if (event.target.classList.contains('material-status-check')) {
                    const checkbox = event.target;
                    const url = checkbox.dataset.url;
                    const newStatus = checkbox.checked ? '購入済' : '未購入';
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify({ status: newStatus })
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (!data.success) {
                           // alert('ステータス更新に失敗しました。'); // 必要であればエラー表示
                        }
                        // 必要であれば成功時の処理（例：コスト合計の動的更新など）
                    })
                    .catch(error => {
                        console.error('Error updating material status:', error);
                        // alert('ステータス更新中にエラーが発生しました。');
                    });
                }
            });
        });
    </script>
@endsection