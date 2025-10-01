<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Akademik') }}</title>
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="/dashboard">Akademik</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                @auth
                    @role('student')
                        <li class="nav-item"><a class="nav-link" href="{{ route('student.courses') }}">Courses</a></li>
                    @endrole
                    @role('admin')
                        <li class="nav-item"><a class="nav-link" href="{{ route('admin.courses.index') }}">Manage Courses</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('admin.users.index') }}">Manage Users</a></li>
                    @endrole
                @endauth
            </ul>
            <ul class="navbar-nav">
                @auth
                    <li class="nav-item"><span class="navbar-text me-2">{{ auth()->user()->name }}</span></li>
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn btn-outline-light btn-sm">Logout</button>
                        </form>
                    </li>
                @else
                    <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Login</a></li>
                @endauth
            </ul>
        </div>
    </div>
 </nav>

 <div class="container">
     @if (session('status'))
         <div class="alert alert-success">{{ session('status') }}</div>
     @endif
     @yield('content')
 </div>

</body>
</html>
