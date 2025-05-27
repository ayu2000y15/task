@extends('layouts.app')

@section('title', 'ガントチャート')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ filtersOpen: {{ array_filter($filters) ? 'true' : 'false' }}, detailsVisible: true }">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">ガントチャート</h1>
        <div>
            <x-secondary-button @click="filtersOpen = !filtersOpen">
                <i class="fas fa-filter mr-1"></i>フィルター
                <i class="fas fa-chevron-down fa-xs ml-2" x-show="!filtersOpen"></i>
                <i class="fas fa-chevron-up fa-xs ml-2" x-show="filtersOpen" style="display:none;"></i>
            </x-secondary-button>
            @can('create', App\Models\Project::class)
                <x-primary-button class="ml-2" onclick="location.href='{{ route('projects.create') }}'"><i class="fas fa-plus mr-1"></i>新規衣装案件</x-primary-button>
            @endcan
        </div>
    </div>

    <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-3">
        <div>
            <x-secondary-button id="dayViewBtn" class="view-mode active" data-mode="day">日ごとの表示</x-secondary-button>
            <x-secondary-button @click="detailsVisible = !detailsVisible" id="toggleDetailsButton" class="ml-2">
                <i class="fas fa-columns mr-1"></i> <span x-text="detailsVisible ? '詳細を隠す' : '詳細を表示'"></span>
            </x-secondary-button>
        </div>
        <div>
            <x-primary-button id="todayBtn">今日</x-primary-button>
        </div>
    </div>

    <div x-show="filtersOpen" x-collapse class="mb-6" style="display: none;">
        <x-filter-panel :action="route('gantt.index')" :filters="$filters" :all-projects="$allProjects"
            :all-characters="$characters" :all-assignees="$allAssignees" :status-options="$statusOptions"
            :show-date-range-filter="true" />
    </div>

    @if ($projects->isEmpty() || $projects->every(function ($project) {
        return $project->tasks()->whereNull('character_id')->doesntExist() &&
               $project->characters()->whereHas('tasks')->doesntExist() &&
               $project->tasks()->whereNotNull('character_id')->whereNull('parent_id')->doesntExist();
    }))
        <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 rounded-md shadow-sm dark:bg-blue-700 dark:text-blue-100 dark:border-blue-300" role="alert">
            表示する工程がありません。フィルター条件を変更するか、新規衣装案件/工程を作成してください。
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
            <div class="gantt-container overflow-x-auto relative">
                <div class="gantt-scroll-container relative">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border-collapse" id="ganttTable" :class="{ 'details-hidden': !detailsVisible }">
                    <thead class="gantt-header text-xs text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        <tr>
                            <th rowspan="2" class="gantt-sticky-col px-3 py-3 align-top font-medium" style="min-width: 300px;">工程</th>
                            <th rowspan="2" class="detail-column px-3 py-3 align-top font-medium" style="min-width: 100px;">担当者</th>
                            <th rowspan="2" class="detail-column px-3 py-3 align-top font-medium" style="min-width: 70px;">工数</th>
                            <th rowspan="2" class="detail-column px-3 py-3 align-top font-medium" style="min-width: 100px;">開始日</th>
                            <th rowspan="2" class="detail-column px-3 py-3 align-top font-medium" style="min-width: 100px;">完了日</th>
                            <th rowspan="2" class="detail-column px-3 py-3 align-top font-medium" style="min-width: 120px;">ステータス</th>
                            @php
                                $months = [];
                                foreach ($dates as $dateInfo) {
                                    $month = $dateInfo['date']->format('Y-m');
                                    if (!isset($months[$month])) {
                                        $months[$month] = [
                                            'name' => $dateInfo['date']->format('Y年n月'),
                                            'count' => 0
                                        ];
                                    }
                                    $months[$month]['count']++;
                                }
                            @endphp

                            @foreach($months as $month)
                                <th colspan="{{ $month['count'] }}" class="text-center py-1.5 font-medium border-b border-gray-200 dark:border-gray-700">{{ $month['name'] }}</th>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach($dates as $dateInfo)
                                @php
                                    $dateStr = $dateInfo['date']->format('Y-m-d');
                                    $isHoliday = isset($holidays[$dateStr]);
                                    $classes = [];
                                    $dayOfWeekMap = ['0' => '日', '1' => '月', '2' => '火', '3' => '水', '4' => '木', '5' => '金', '6' => '土'];
                                    $dayOfWeek = $dayOfWeekMap[$dateInfo['date']->format('w')];

                                    if ($dateInfo['is_saturday']) $classes[] = 'saturday'; // Handled by app.css
                                    elseif ($dateInfo['is_sunday'] || $isHoliday) $classes[] = 'sunday'; // Handled by app.css
                                    if ($dateInfo['date']->isSameDay($today)) $classes[] = 'today'; // Handled by app.css
                                @endphp
                                <th class="gantt-cell {{ implode(' ', $classes) }} font-normal border border-gray-200 dark:border-gray-700" style="min-width: 35px;"
                                    title="{{ $isHoliday ? $holidays[$dateStr]->name : '' }}" data-date="{{ $dateStr }}">
                                    <div class="flex flex-col items-center text-xs">
                                        <span>{{ $dateInfo['day'] }}</span>
                                        <span class="text-[0.7rem]">{{ $dayOfWeek }}</span>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        @foreach($projects as $project)
                            @if($project->tasks()->whereNull('character_id')->whereNull('parent_id')->exists() || $project->characters()->whereHas('tasks')->exists() || $project->tasks()->whereNotNull('character_id')->whereNull('parent_id')->exists())
                                <tr class="project-header project-{{ $project->id }}-tasks task-level-0 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <td class="gantt-sticky-col px-3 py-2.5">
                                        <div class="flex justify-between items-start">
                                            <div class="flex items-start gantt-task-name-container">
                                                <span class="toggle-children cursor-pointer mr-2 mt-px" data-project-id="{{ $project->id }}">
                                                    <i class="fas fa-chevron-down"></i>
                                                </span>
                                                <div class="w-5 h-5 flex-shrink-0 flex items-center justify-center rounded text-white text-xs font-bold mr-2 mt-px"
                                                    style="background-color: {{ $project->color }};">
                                                    {{ mb_substr($project->title, 0, 1) }}
                                                </div>
                                                <a href="{{ route('projects.show', $project) }}" class="font-medium text-gray-800 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 gantt-task-name-link">{{ $project->title }}</a>
                                                @if($project->is_favorite)
                                                    <i class="fas fa-star text-yellow-400 ml-2"></i>
                                                @endif
                                            </div>
                                            <div class="task-actions flex space-x-1 flex-shrink-0">
                                                <a href="{{ route('projects.tasks.create', $project) }}"
                                                    class="p-1 text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300" title="工程追加">
                                                    <i class="fas fa-plus"></i>
                                                </a>
                                                <a href="{{ route('projects.edit', $project) }}" class="p-1 text-yellow-500 hover:text-yellow-700 dark:text-yellow-400 dark:hover:text-yellow-300" title="編集">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="detail-column px-3 py-2.5 text-gray-500 dark:text-gray-400">&nbsp;</td> {{-- 担当者 --}}
                                    <td class="detail-column px-3 py-2.5 text-gray-500 dark:text-gray-400">&nbsp;</td> {{-- 工数 --}}
                                    <td class="detail-column px-3 py-2.5 text-gray-500 dark:text-gray-400">&nbsp;</td> {{-- 開始日 --}}
                                    <td class="detail-column px-3 py-2.5 text-gray-500 dark:text-gray-400">&nbsp;</td> {{-- 完了日 --}}
                                    <td class="detail-column px-3 py-2.5 text-gray-500 dark:text-gray-400">&nbsp;</td> {{-- ステータス --}}

                                    @php
                                        $projectStartDate = $project->start_date?->format('Y-m-d');
                                        $projectEndDate = $project->end_date?->format('Y-m-d');
                                        $startPosition = -1;
                                        $projectLength = 0;

                                        if ($projectStartDate && $projectEndDate) {
                                            foreach ($dates as $index => $dateInfoLoop) {
                                                $currentDate = $dateInfoLoop['date']->format('Y-m-d');
                                                if ($currentDate === $projectStartDate)
                                                    $startPosition = $index;
                                                if ($currentDate >= $projectStartDate && $currentDate <= $projectEndDate)
                                                    $projectLength++;
                                            }
                                        }
                                    @endphp

                                    @for($i = 0; $i < count($dates); $i++)
                                        @php
                                            $dateStr = $dates[$i]['date']->format('Y-m-d');
                                            $isHoliday = isset($holidays[$dateStr]);
                                            $cellClasses = [];
                                            if ($dates[$i]['is_saturday'])
                                                $cellClasses[] = 'saturday';
                                            elseif ($dates[$i]['is_sunday'] || $isHoliday) $cellClasses[] = 'sunday';
                                            if ($dates[$i]['date']->isSameDay($today)) $cellClasses[] = 'today';

                                            $style = '';
                                            $hasBar = $startPosition >= 0 && $i >= ($startPosition) && $i < ($startPosition + $projectLength);
                                            if ($hasBar) {
                                                $cellClasses[] = 'has-bar';
                                                // HEX to RGBA conversion
                                                $color = ltrim($project->color ?? '#6c757d', '#');
                                                $rgb = strlen($color) == 6 ? sscanf($color, "%2x%2x%2x") : (strlen($color) == 3 ? sscanf(str_repeat(substr($color,0,1),2).str_repeat(substr($color,1,1),2).str_repeat(substr($color,2,1),2), "%2x%2x%2x") : [108, 117, 125]);

                                                $rgbaColor = sprintf('rgba(%d, %d, %d, 0.7)', $rgb[0], $rgb[1], $rgb[2]);
                                                if ($dates[$i]['date']->isSameDay($today)) {
                                                    // 今日の場合、背景色を重ねて両方見えるようにする
                                                    $style = "background-image: linear-gradient({$rgbaColor}, {$rgbaColor});";
                                                } else {
                                                    $style = "background-color: {$rgbaColor};";
                                                }
                                            }
                                        @endphp
                                        <td class="gantt-cell {{ implode(' ', $cellClasses) }} p-0 relative border-x border-gray-200 dark:border-gray-700" data-date="{{ $dateStr }}" style="{{ $style }}">
                                            @if($hasBar)
                                                {{-- gantt-bar div is removed --}}
                                                <div class="gantt-tooltip" style="top: -50px; left: 50%; transform: translateX(-50%);">
                                                    <div class="gantt-tooltip-content">
                                                        {{ $project->title }}<br>
                                                        期間: {{ $project->start_date->format('Y/m/d') }} 〜
                                                        {{ $project->end_date->format('Y/m/d') }}
                                                    </div>
                                                    <div class="gantt-tooltip-arrow"></div>
                                                </div>
                                            @endif
                                        </td>
                                    @endfor
                                </tr>
                                {{-- 案件全体の工程 (キャラクターに紐づかない) --}}
                                @php
                                    $projectLevelTasks = $project->tasks
                                        ->whereNull('character_id')
                                        ->whereNull('parent_id')
                                        ->sortBy(function ($task, $key) {
                                            // [is_null(start_date), start_date, name] の順でソート
                                            return [$task->start_date === null, $task->start_date, $task->name];
                                        });
                                @endphp
                                @include('gantt.partials.task_rows', ['tasks' => $projectLevelTasks, 'project' => $project, 'dates' => $dates, 'holidays' => $holidays, 'today' => $today, 'level' => 0, 'parent_character_id' => null])

                                {{-- キャラクターごとの工程 --}}
                                @foreach($project->characters->sortBy('name') as $character)
                                    @php
                                        $characterTasks = $character->tasks
                                            ->whereNull('parent_id')
                                            ->sortBy(function ($task, $key) {
                                                return [$task->start_date === null, $task->start_date, $task->name];
                                            });
                                    @endphp
                                    <tr class="character-header project-{{ $project->id }}-tasks task-level-0 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700"
                                        data-project-id-for-toggle="{{ $project->id }}">
                                        <td class="gantt-sticky-col px-3 py-2.5">
                                            <div class="flex items-start" style="padding-left: 20px;">
                                                <span class="toggle-children cursor-pointer mr-2 mt-px" data-character-id="{{ $character->id }}"
                                                    data-project-id-of-char="{{ $project->id }}">
                                                    <i class="fas fa-chevron-down"></i>
                                                </span>
                                                <i class="fas fa-user-circle text-sky-500 mr-2 mt-px text-base"></i>
                                                <span class="font-medium text-gray-800 dark:text-gray-100">{{ $character->name }}</span>
                                            </div>
                                        </td>
                                        <td class="detail-column px-3 py-2.5 text-gray-500 dark:text-gray-400">&nbsp;</td>
                                        <td class="detail-column px-3 py-2.5 text-gray-500 dark:text-gray-400">&nbsp;</td>
                                        <td class="detail-column px-3 py-2.5 text-gray-500 dark:text-gray-400">&nbsp;</td>
                                        <td class="detail-column px-3 py-2.5 text-gray-500 dark:text-gray-400">&nbsp;</td>
                                        <td class="detail-column px-3 py-2.5 text-gray-500 dark:text-gray-400">&nbsp;</td>
                                        @for($i = 0; $i < count($dates); $i++)
                                            @php
                                                $dateStr = $dates[$i]['date']->format('Y-m-d');
                                                $isHoliday = isset($holidays[$dateStr]);
                                                $cellClasses = [];
                                                if ($dates[$i]['is_saturday'])
                                                    $cellClasses[] = 'saturday';
                                                elseif ($dates[$i]['is_sunday'] || $isHoliday) $cellClasses[] = 'sunday';
                                                if ($dates[$i]['date']->isSameDay($today))
                                                    $cellClasses[] = 'today';
                                            @endphp
                                            <td class="gantt-cell {{ implode(' ', $cellClasses) }} p-0 border-x border-gray-200 dark:border-gray-700" data-date="{{ $dateStr }}">&nbsp;</td>
                                        @endfor
                                    </tr>
                                    @include('gantt.partials.task_rows', ['tasks' => $characterTasks, 'project' => $project, 'character' => $character, 'dates' => $dates, 'holidays' => $holidays, 'today' => $today, 'level' => 1, 'parent_character_id' => $character->id])
                                @endforeach
                            @endif
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    @endif

    <x-modal name="ganttFileUploadModal" maxWidth="2xl">
        <div class="p-6" x-data="{ modalProjectId: null, modalTaskId: null, modalTaskName: '' }"
             @open-modal.window="if ($event.detail.name === 'ganttFileUploadModal') {
                                    modalProjectId = $event.detail.projectId;
                                    modalTaskId = $event.detail.taskId;
                                    modalTaskName = $event.detail.taskName;
                                    Alpine.store('ganttDropzone').init(modalProjectId, modalTaskId);
                                    Alpine.store('ganttDropzone').fetchFiles(modalProjectId, modalTaskId);
                                }">
            <div class="flex justify-between items-center pb-3 border-b dark:border-gray-700">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    ファイルアップロード: <span x-text="modalTaskName" class="font-semibold"></span>
                </h2>
                <button @click="$dispatch('close-modal', { name: 'ganttFileUploadModal' })" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="mt-4">
                <form action="#" method="post" class="dropzone dropzone-custom-style mb-3" id="gantt-file-upload-dropzone-alpine">
                    @csrf
                    <div class="dz-message text-center" data-dz-message>
                        <p class="mb-2">ここにファイルをドラッグ＆ドロップ</p>
                        <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">または</p>
                        <button type="button" class="dz-button-bootstrap"><i class="fas fa-folder-open mr-1"></i>ファイルを選択</button>
                    </div>
                </form>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-1">アップロード済みファイル</h3>
                <div class="max-h-48 overflow-y-auto border rounded-md dark:border-gray-600">
                    <ul class="divide-y dark:divide-gray-700" id="gantt-uploaded-file-list-alpine">
                        <li class="p-3 text-center text-sm text-gray-500 dark:text-gray-400">ファイルを読み込み中...</li>
                    </ul>
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-2">
                <x-secondary-button @click="$dispatch('close-modal', { name: 'ganttFileUploadModal' })">閉じる</x-secondary-button>
                <x-primary-button @click="Alpine.store('ganttDropzone').processQueue()" id="ganttProcessUploadQueueBtnAlpine"><i class="fas fa-upload mr-1"></i>選択ファイルをアップロード</x-primary-button>
            </div>
        </div>
    </x-modal>
</div>
@endsection

@section('scripts')
    {{-- jQuery UI は現在使用されていないため削除 --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        Dropzone.autoDiscover = false; // グローバルスコープで最初に設定
        const overlay = document.getElementById('upload-loading-overlay'); // オーバーレイ要素をキャッシュ

        // Alpine.js store for Dropzone instance and related data
        document.addEventListener('alpine:init', () => {
            Alpine.store('ganttDropzone', {
                instance: null,
                currentProjectId: null,
                currentTaskId: null,
                init(projectId, taskId) {
                    this.currentProjectId = projectId;
                    this.currentTaskId = taskId;
                    const dropzoneElement = document.getElementById('gantt-file-upload-dropzone-alpine');
                    if (!dropzoneElement) return;

                    const csrfTokenEl = document.querySelector('meta[name="csrf-token"]');
                    if (!csrfTokenEl || !csrfTokenEl.getAttribute('content')) {
                        console.error("CSRF token not found for Gantt Dropzone.");
                        return;
                    }
                    const uploadUrl = `/projects/${this.currentProjectId}/tasks/${this.currentTaskId}/files`;

                    if (this.instance) {
                        this.instance.destroy();
                    }

                    const clickableButton = dropzoneElement.querySelector('.dz-button-bootstrap');

                    this.instance = new Dropzone(dropzoneElement, {
                        url: uploadUrl,
                        method: 'post',
                        clickable: clickableButton || true, // Fallback if button not found
                        paramName: "file",
                        maxFilesize: 100, // MB
                        addRemoveLinks: true,
                        dictRemoveFile: "×",
                        headers: { 'X-CSRF-TOKEN': csrfTokenEl.getAttribute('content') },
                        autoProcessQueue: false, // Important: process manually
                        init: function() {
                            this.on("success", (file, response) => {
                                Alpine.store('ganttDropzone').fetchFiles(); // Use current project/task ID from store
                                this.removeFile(file);
                            });
                            this.on("error", (file, message) => {
                                let errorMessage = "アップロードに失敗しました。";
                                if (typeof message === "string") errorMessage = message;
                                else if (message && message.errors && message.errors.file) errorMessage = message.errors.file[0];
                                else if (message && message.message) errorMessage = message.message;
                                alert("エラー: " + errorMessage);
                                this.removeFile(file);
                                if (overlay) overlay.style.display = 'none';
                            });
                            this.on("queuecomplete", () => {
                                if (overlay) overlay.style.display = 'none';
                                if (this.getQueuedFiles().length === 0 && this.getUploadingFiles().length === 0) {
                                    // Handle completion alerts if needed
                                }
                            });
                            // Add other event handlers as needed
                        }
                    });
                },
                fetchFiles(projectId, taskId) {
                    // If called without args, use stored values
                    const pId = projectId || this.currentProjectId;
                    const tId = taskId || this.currentTaskId;
                    if (!pId || !tId) return;

                    const fileListEl = document.getElementById('gantt-uploaded-file-list-alpine');
                    if (!fileListEl) return;
                    fileListEl.innerHTML = '<li class="p-3 text-center text-sm text-gray-500 dark:text-gray-400">ファイルを読み込み中...</li>';

                    axios.get(`/projects/${pId}/tasks/${tId}/files`)
                        .then(response => { fileListEl.innerHTML = response.data; })
                        .catch(error => {
                            fileListEl.innerHTML = '<li class="p-3 text-center text-sm text-red-600 dark:text-red-400">ファイル一覧の取得に失敗しました。</li>';
                            console.error("Error fetching files for Gantt modal:", error);
                        });
                },
                processQueue() {
                    if (overlay) overlay.style.display = 'flex';
                    if (this.instance && this.instance.getQueuedFiles().length > 0) {
                        this.instance.processQueue();
                    } else {
                        if (overlay) overlay.style.display = 'none';
                        alert('アップロードするファイルが選択されていません。');
                    }
                },
                removeAllFiles() {
                    if (this.instance) {
                        this.instance.removeAllFiles(true);
                    }
                    const fileListEl = document.getElementById('gantt-uploaded-file-list-alpine');
                    if (fileListEl) {
                        fileListEl.innerHTML = '<li class="p-3 text-center text-sm text-gray-500 dark:text-gray-400">ファイルを読み込み中...</li>';
                    }
                }
            });
        });


        $(document).ready(function () {
            // Details toggle (jQuery for now, could be Alpine if table re-renders are an issue)
            $('#ganttTable').addClass('day-view');
            // jQuery selector for toggleDetailsButton and its click event is removed as it's handled by Alpine now.
            // :class="{ 'details-hidden': !detailsVisible }" on the table handles the class toggling.

            // Child row toggling (jQuery for now for deep nesting, review for Alpine conversion if feasible)
            $(document).on('click', '.toggle-children', function () {
                const $toggleSpan = $(this);
                const $icon = $toggleSpan.find('i');
                const isExpanded = $icon.hasClass('fa-chevron-down');
                const $parentRow = $toggleSpan.closest('tr');
                let $directChildrenToToggle;
                if ($parentRow.hasClass('project-header')) {
                    const projectId = $toggleSpan.data('project-id');
                    $directChildrenToToggle = $(`tr.project-${projectId}-tasks.task-level-0:not(.project-header)`);
                } else if ($parentRow.hasClass('character-header')) {
                    const characterId = $toggleSpan.data('character-id');
                    const projectIdOfChar = $toggleSpan.data('project-id-of-char');
                    $directChildrenToToggle = $(`tr.project-${projectIdOfChar}-tasks.task-parent-char-${characterId}.task-level-1`);
                } else {
                    const taskId = $toggleSpan.data('task-id');
                    if (taskId) {
                        $directChildrenToToggle = $(`tr.task-parent-${taskId}`);
                    }
                }
                if (!$directChildrenToToggle || $directChildrenToToggle.length === 0) {
                    if (isExpanded) $icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                    else $icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                    return;
                }
                if (isExpanded) {
                    $icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                    function closeAllDescendants($elements) {
                        $elements.each(function () {
                            const $currentElement = $(this);
                            $currentElement.hide();
                            const $toggleSpanOnCurrentElement = $currentElement.find('.toggle-children').first();
                            if ($toggleSpanOnCurrentElement.length > 0) {
                                $toggleSpanOnCurrentElement.find('i').removeClass('fa-chevron-down').addClass('fa-chevron-right');
                                let $grandChildren;
                                const characterId = $toggleSpanOnCurrentElement.data('character-id');
                                const taskId = $toggleSpanOnCurrentElement.data('task-id');
                                if (characterId) {
                                    const projectIdOfChar = $toggleSpanOnCurrentElement.data('project-id-of-char');
                                    $grandChildren = $(`tr.project-${projectIdOfChar}-tasks.task-parent-char-${characterId}.task-level-1`);
                                } else if (taskId) {
                                    $grandChildren = $(`tr.task-parent-${taskId}`);
                                }
                                if ($grandChildren && $grandChildren.length > 0) {
                                    closeAllDescendants($grandChildren);
                                }
                            }
                        });
                    }
                    closeAllDescendants($directChildrenToToggle);
                } else {
                    $icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                    $directChildrenToToggle.show();
                }
            });

            // Scroll to Today
            function scrollToToday() {
                const $todayCell = $('.gantt-cell.today').first();
                if ($todayCell.length) {
                    const ganttContainer = $('.gantt-container');
                    const stickyColWidth = $('.gantt-sticky-col').first().outerWidth() || 0;
                    // Adjust offset calculation relative to the scrollable container
                    const cellOffsetLeft = $todayCell.position().left;
                    const targetScroll = cellOffsetLeft - stickyColWidth - (ganttContainer.width() / 3) ; // scroll to make it visible near left
                    ganttContainer.animate({ scrollLeft: Math.max(0, ganttContainer.scrollLeft() + targetScroll ) }, 300);
                }
            }
            $('#todayBtn').on('click', scrollToToday);
            // Initial scroll to today if applicable
            // scrollToToday(); // Uncomment if you want to scroll on page load

            // Status select AJAX (can remain as is, but select is now Tailwind styled)
            $(document).on('change', '.status-select', function () {
                const taskId = $(this).data('task-id');
                const projectId = $(this).data('project-id');
                const status = $(this).val();
                let progress = 0;
                if (status === 'completed') {
                    progress = 100;
                } else if (status === 'not_started' || status === 'cancelled') {
                    progress = 0;
                }
                // Consider adding a loading indicator here
                axios.post(`/projects/${projectId}/tasks/${taskId}/progress`, {
                    status: status,
                    progress: progress
                }, {
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                })
                .then(response => {
                    if (!response.data.success) {
                        console.error('ステータス更新に失敗しました: ' + (response.data.message || ''));
                        // Optionally revert select value or show user error
                    }
                    location.reload(); // Consider more targeted UI update
                })
                .catch(error => {
                    console.error('ステータス更新中にエラーが発生しました。', error);
                    // Optionally revert select value or show user error
                });
            });

            // Inline assignee edit (can remain as is, input will be Tailwind styled)
            $(document).on('click', '.editable-cell[data-field="assignee"]', function () {
                const cell = $(this);
                if (cell.find('input').length) return; // Already in edit mode

                const originalValue = cell.find('span').text().trim() === '-' ? '' : cell.find('span').text().trim();
                const taskId = cell.data('task-id');
                const projectId = cell.data('project-id');

                cell.find('span').hide(); // Hide the span
                const input = $(`<input type="text" class="form-input text-sm p-1 border border-blue-500 rounded dark:bg-gray-700 dark:text-gray-200 w-full" value="${originalValue}">`);
                cell.append(input);
                input.focus().select();

                function saveChanges() {
                    const newValue = input.val().trim();
                    input.remove(); // Remove input field
                    cell.find('span').text(newValue || '-').show(); // Update span and show it

                    if (newValue !== originalValue) {
                        axios.post(`/projects/${projectId}/tasks/${taskId}/assignee`, {
                            assignee: newValue
                        }, {
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                        })
                        .then(response => {
                            if (!response.data.success) {
                                console.error('担当者の更新に失敗しました: ' + (response.data.message || ''));
                                cell.find('span').text(originalValue || '-'); // Revert on failure
                            } else {
                                cell.data('current-value', newValue); // Update current value stored on cell
                            }
                        })
                        .catch(error => {
                            console.error('担当者更新中にエラーが発生しました。', error);
                            cell.find('span').text(originalValue || '-'); // Revert on error
                        });
                    }
                }
                input.on('blur', saveChanges);
                input.on('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        saveChanges();
                    } else if (e.key === 'Escape') {
                        input.val(originalValue); // Revert input value
                        saveChanges(); // This will effectively do nothing if value hasn't changed from original
                    }
                });
            });

            // Modal related logic is now handled by Alpine.js for opening.
            // Dropzone init and file fetching is done via Alpine store: Alpine.store('ganttDropzone').init(...) and .fetchFiles()
            // Triggering queue processing is via Alpine store: Alpine.store('ganttDropzone').processQueue()
            // Clearing files on modal close can be handled by listening to the close-modal event if necessary
            window.addEventListener('close-modal', (event) => {
                if (event.detail.name === 'ganttFileUploadModal') {
                    Alpine.store('ganttDropzone').removeAllFiles();
                }
            });

            // File deletion from the modal list (event delegation on a static parent)
            $(document).on('click', '#gantt-uploaded-file-list-alpine .delete-file-btn', function (e) {
                e.preventDefault();
                const button = $(this);
                const url = button.data('url');
                if (!url) {
                    console.error("Delete URL not found on Gantt delete button");
                    return;
                }
                if (confirm('本当にこのファイルを削除しますか？')) {
                    axios.delete(url, { headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } })
                        .then(function (response) {
                            if (response.data.success) {
                                const itemToRemove = button.closest('li'); // Assuming files are in <li>
                                if (itemToRemove) itemToRemove.remove();

                                const fileListEl = document.getElementById('gantt-uploaded-file-list-alpine');
                                if (fileListEl && fileListEl.children.length === 0) {
                                    fileListEl.innerHTML = '<li class="p-3 text-center text-sm text-gray-500 dark:text-gray-400">アップロードされたファイルはありません。</li>';
                                }
                            } else {
                                alert('ファイルの削除に失敗しました。\n' + (response.data.message || ''));
                            }
                        })
                        .catch(function (error) {
                            alert('ファイルの削除中にエラーが発生しました。');
                            console.error(error);
                        });
                }
            });
        });
    </script>
@endsection