@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Courses</h3>
    <a href="{{ route('admin.courses.create') }}" class="btn btn-primary">New</a>
  </div>
<table class="table table-striped">
    <thead><tr><th>Code</th><th>Name</th><th>Credits</th><th></th></tr></thead>
    <tbody>
    @foreach($courses as $course)
        <tr>
            <td>{{ $course->code }}</td>
            <td>{{ $course->name }}</td>
            <td>{{ $course->credits }}</td>
            <td class="text-end">
                <a href="{{ route('admin.courses.edit',$course) }}" class="btn btn-sm btn-secondary">Edit</a>
                <form method="POST" action="{{ route('admin.courses.destroy',$course) }}" class="d-inline" onsubmit="return confirm('Delete?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">Delete</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $courses->links() }}
@endsection
