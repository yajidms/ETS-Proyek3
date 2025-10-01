@extends('layouts.app')

@section('content')
<div class="p-4 bg-light rounded-3">
    <h1 class="display-6">Dashboard</h1>
    <p class="lead">Welcome, {{ auth()->user()->name }}.</p>
    @role('admin')
        <p>You have admin access.</p>
    @endrole
    @role('student')
        <p>You have student access.</p>
    @endrole
</div>
@endsection
