<li class="flex items-center group mt-2 {{ $item->is_completed ? 'opacity-50' : '' }}">
    <input type="checkbox"
        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 request-item-checkbox"
        data-item-id="{{ $item->id }}" {{ $item->is_completed ? 'checked' : '' }}>
    <div class="ml-3">
        <span
            class="item-content text-sm text-gray-800 dark:text-gray-200 {{ $item->is_completed ? 'line-through' : '' }}">
            {{ $item->content }}
        </span>
        <span class="text-xs text-gray-500 dark:text-gray-400 block">
            (依頼: <a href="{{ route('requests.index') }}" class="hover:underline">{{ $item->request->title }}</a>)
        </span>
    </div>
</li>