@extends('layouts.app')

@section('title', 'キャラクター編集 - ' . $character->name)

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>キャラクター編集: {{ $character->name }}</h1>
            <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> 「{{ $project->title }}」に戻る
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('projects.characters.update', [$project, $character]) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label">キャラクター名 <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $character->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">備考</label>
                        <textarea name="description" id="description"
                            class="form-control @error('description') is-invalid @enderror"
                            rows="3">{{ old('description', $character->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">更新</button>
                </form>
            </div>
        </div>
    </div>
@endsection