<div class="row mb-4">
    <div class="col-md-8">
        <label for="{{ $name }}" @if(isset($display) && $display) style="display:none" @endif
            class="form-label fs-5 mb-3">
            {{ $label }}
            @if(isset($required) && $required)
                <span class="required badge bg-danger ms-2" style="color: white;">必須</span>
            @endif
        </label>

        @if($type == 'textarea')
            <textarea id="{{ $name }}" name="{{ $name }}" class="form-control" rows="{{ $rows ?? 5 }}" @if(isset($display) && $display) style="display:none" @endif @if(isset($required) && $required) required
            @endif>{{ $value ?? '' }}</textarea>
        @elseif($type == 'select')
            <select id="{{ $name }}" name="{{ $name }}" class="form-select" @if(isset($display) && $display)
            style="display:none" @endif @if(isset($required) && $required) required @endif>
                <option value="">選択してください</option>
                @if(isset($options) && is_array($options))
                    @foreach($options as $option)
                        <option value="{{ $option['value'] }}" {{ isset($value) && $value == $option['value'] ? 'selected' : '' }}>
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                @endif
            </select>
        @elseif($type == 'file')
                @include('components.file-upload', [
                    'fieldName' => $name,
                    'required' => $required ?? false,
                    'currentFiles' => $value ?? null,
                    'dataId' => $dataId ?? null
                ])
        @elseif($type == 'files')
                    @include('components.file-upload', [
                        'fieldName' => $name,
                        'multiple' => true,
                        'required' => $required ?? false,
                        'currentFiles' => $value ?? null,
                        'dataId' => $dataId ?? null
                    ])
        @elseif($type == 'date' || $type == 'month')
                    <input type="{{ $type }}"
                            id="{{ $name }}"
                            name="{{ $name }}"
                            class="form-control"
                            value="{{ $value ?? date('Y-m-d') }}"
                            @if(isset($display) && $display) style="display:none" @endif
                            @if(isset($required) && $required) required @endif>
        @else
            <input type="{{ $type }}"
                            id="{{ $name }}"
                            name="{{ $name }}"
                            class="form-control"
                            value="{{ $value ?? '' }}"
                            @if(isset($display) && $display) style="display:none" @endif
                            @if(isset($required) && $required) required @endif>
        @endif

        @if(isset($error))
            <div class="text-danger mt-1">
                {{ $error }}
            </div>
        @endif
    </div>
</div>

