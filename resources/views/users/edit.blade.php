@extends('layouts.app')

@section('title', 'ユーザー役割編集')

@section('content')
<div class="container">
    <h1>ユーザー役割編集</h1>

    <div class="card">
        <div class="card-header">
            {{ $user->name }} ({{ $user->email }})
        </div>
        <div class="card-body">
            <form action="{{ route('users.update', $user) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">役割</label>
                    @foreach($roles as $role)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->id }}" id="role_{{ $role->id }}"
                                {{ $user->roles->contains($role->id) ? 'checked' : '' }}>
                            <label class="form-check-label" for="role_{{ $role->id }}">
                                {{ $role->display_name }}
                            </label>
                        </div>
                    @endforeach
                </div>

                <button type="submit" class="btn btn-primary">更新</button>
                <a href="{{ route('users.index') }}" class="btn btn-secondary">キャンセル</a>
            </form>
        </div>
    </div>
</div>
@endsection