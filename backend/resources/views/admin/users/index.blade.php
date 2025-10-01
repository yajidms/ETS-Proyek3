@extends('layouts.app')

@section('content')
<h3>Users</h3>
<table class="table table-striped mt-3">
    <thead><tr><th>Name</th><th>Email</th><th>Roles</th><th>Active</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
    @foreach($users as $user)
        <tr>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ implode(', ', $user->getRoleNames()->toArray()) }}</td>
            <td>{!! $user->is_active ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' !!}</td>
            <td class="text-end">
                <form method="POST" action="{{ route('admin.users.toggle',$user) }}" class="d-inline">
                    @csrf
                    <button class="btn btn-sm btn-warning">Toggle Active</button>
                </form>
                <form method="POST" action="{{ route('admin.users.role',$user) }}" class="d-inline">
                    @csrf
                    <select name="role" class="form-select d-inline w-auto">
                        <option value="admin" @selected($user->hasRole('admin'))>admin</option>
                        <option value="student" @selected($user->hasRole('student'))>student</option>
                    </select>
                    <button class="btn btn-sm btn-primary">Set Role</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $users->links() }}
@endsection
