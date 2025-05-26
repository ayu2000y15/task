<div class="cost-summary">
    合計: {{ number_format($character->costs->sum('amount')) }}円
</div>
<table class="table table-sm character-table">
    <thead>
        <tr>
            <th>日付</th>
            <th>内容</th>
            <th>種別</th>
            <th>金額</th>
            <th width="30"></th>
        </tr>
    </thead>
    <tbody>
        @forelse($character->costs as $cost)
            <tr>
                <td>{{ $cost->cost_date->format('m/d') }}</td>
                <td>{{ $cost->item_description }}</td>
                <td><span class="badge badge-status bg-info">{{ $cost->type }}</span></td>
                <td>{{ number_format($cost->amount) }}円</td>
                <td>
                    <form action="{{ route('projects.characters.costs.destroy', [$project, $character, $cost]) }}"
                        method="POST" onsubmit="return confirm('削除しますか？');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center text-muted">コストなし</td>
            </tr>
        @endforelse
    </tbody>
</table>
<div class="character-form">
    <form action="{{ route('projects.characters.costs.store', [$project, $character]) }}" method="POST" class="row g-2">
        @csrf
        <div class="col-3">
            <input type="text" name="item_description" class="form-control form-control-sm" placeholder="内容" required>
        </div>
        <div class="col-3">
            <input type="number" name="amount" class="form-control form-control-sm" placeholder="金額" required>
        </div>
        <div class="col-3">
            <input type="date" name="cost_date" class="form-control form-control-sm"
                value="{{ today()->format('Y-m-d') }}" required>
        </div>
        <div class="col-3">
            <select name="type" class="form-select form-select-sm">
                <option value="材料費">材料費</option>
                <option value="作業費">作業費</option>
                <option value="その他">その他</option>
            </select>
        </div>
        <div class="col-3"> {{-- ボタンを独立した行または適切な列配置にする --}}
            <button type="submit" class="btn btn-primary btn-sm w-100">追加</button>
        </div>
    </form>
</div>