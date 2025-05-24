@extends('layouts.app')

@section('title', '権限設定')

@section('content')
<div class="container">
    <h1>権限設定</h1>

    <div class="accordion" id="rolesAccordion">
        @foreach($roles as $role)
        <div class="accordion-item">
            <h2 class="accordion-header" id="heading_{{ $role->id }}">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_{{ $role->id }}" aria-expanded="false" aria-controls="collapse_{{ $role->id }}">
                    {{ $role->display_name }}
                </button>
            </h2>
            <div id="collapse_{{ $role->id }}" class="accordion-collapse collapse" aria-labelledby="heading_{{ $role->id }}" data-bs-parent="#rolesAccordion">
                <div class="accordion-body">
                    <form action="{{ route('roles.update', $role) }}" method="POST">
                        @csrf
                        @method('PUT')

                        @foreach($permissions as $groupName => $permissionGroup)
                            <fieldset class="mb-3">
                                <legend class="fs-6">{{ ucfirst($groupName) }}</legend>
                                @foreach($permissionGroup as $permission)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="perm_{{ $role->id }}_{{ $permission->id }}"
                                            {{ $role->permissions->contains($permission->id) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="perm_{{ $role->id }}_{{ $permission->id }}">
                                            {{ $permission->display_name }}
                                        </label>
                                    </div>
                                @endforeach
                            </fieldset>
                        @endforeach

                        <button type="submit" class="btn btn-primary">「{{ $role->display_name }}」の権限を保存</button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection