<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'My App')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-pO1F5Gtb3N/..." crossorigin="anonymous" referrerpolicy="no-referrer" />

    @stack('styles')
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="/">Sai Agency</a>
        <div class="navbar-nav ms-auto">
            <a class="navbar-brand" href="{{ route('home') }}">
                <i class="bi bi-house-door-fill"></i> 
            </a>
            <a class="nav-link" href="{{ route('fileUpload') }}">
                <i class="bi bi-upload"></i> File Upload
            </a>

            <a class="nav-link" href="{{ route('beats.index') }}">
                <i class="bi bi-list-check"></i> Beats
            </a>
            <a class="nav-link" href="{{ route('party-sales.index') }}">
                <i class="bi bi-list-check"></i> Party Sales
            </a>
        </div>
    </div>
</nav>


<div class="container mt-4 ms-0">
    @yield('content')
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

@stack('scripts')
</body>
</html>
