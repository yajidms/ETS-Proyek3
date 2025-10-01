@extends('layouts.app')

@section('content')
<h3>Create Course</h3>
<form method="POST" action="{{ route('admin.courses.store') }}" class="mt-3">
    @csrf
    <div class="mb-3"><label class="form-label">Code</label>
        <input name="code" value="{{ old('code') }}" class="form-control" required>
        @error('code')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3"><label class="form-label">Name</label>
        <input name="name" value="{{ old('name') }}" class="form-control" required>
        @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3"><label class="form-label">Description</label>
        <textarea name="description" class="form-control">{{ old('description') }}</textarea>
    </div>
    <div class="mb-3"><label class="form-label">Credits</label>
        <input type="number" name="credits" value="{{ old('credits',3) }}" class="form-control" required>
        @error('credits')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <button class="btn btn-primary">Save</button>
    <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary">Back</a>
</form>
@endsection
