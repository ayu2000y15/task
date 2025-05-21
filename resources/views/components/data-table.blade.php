<div class="table-container">
    <table class="table table-striped table-hover wide-table">
        <thead class="{{ $headerClass ?? 'table-primary' }} sticky-header">
            <tr>
                @foreach($columns as $column)
                    <th class="{{ $column['class'] ?? '' }}">{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody {{ isset($sortable) && $sortable ? 'id="sortable-items"' : '' }}>
            {{ $slot }}
        </tbody>
    </table>
</div>

