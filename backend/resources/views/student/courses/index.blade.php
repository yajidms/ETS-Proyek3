@extends('layouts.app')

@section('content')
<h3>Available Courses</h3>
<table class="table table-striped mt-3">
    <thead><tr><th>Code</th><th>Name</th><th>Credits</th><th class="text-end"></th></tr></thead>
    <tbody>
    @foreach($courses as $course)
        <tr>
            <td>{{ $course->code }}</td>
            <td>{{ $course->name }}</td>
            <td>{{ $course->credits }}</td>
            <td class="text-end">
                <form method="POST" action="{{ route('student.courses.enroll',$course) }}">
                    @csrf
                    <button class="btn btn-sm btn-success">Enroll</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $courses->links() }}
@endsection
