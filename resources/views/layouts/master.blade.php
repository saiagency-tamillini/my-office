<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Sai Agency')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-pO1F5Gtb3N/..." crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    @stack('styles')
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container p-0">
        <a class="navbar-brand" href="/">Sai Agency</a>
        <div class="navbar-nav">
            <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
                <i class="bi bi-house-door-fill"></i>
            </a>

            <a class="nav-link {{ request()->routeIs('fileUpload') ? 'active' : '' }}" href="{{ route('fileUpload') }}">
                <i class="bi bi-upload"></i> File Upload
            </a>

            <a class="nav-link {{ request()->routeIs('beats.*') ? 'active' : '' }}" href="{{ route('beats.index') }}">
                <i class="bi bi-list-check"></i> Beats
            </a>

            <a class="nav-link {{ request()->routeIs('party-sales.*') ? 'active' : '' }}" href="{{ route('party-sales.index') }}">
                <i class="bi bi-list-check"></i> Party Sales
            </a>

            <a class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}">
                <i class="bi bi-list-check"></i> Customers
            </a>

            <a class="nav-link {{ request()->routeIs('reportTable') ? 'active' : '' }}" href="{{ route('reportTable') }}">
                <i class="bi bi-list-check"></i> Sales Report
            </a>

            <a class="nav-link {{ request()->routeIs('salesman') ? 'active' : '' }}" href="{{ route('salesman') }}">
                <i class="bi bi-list-check"></i> Sales Man
            </a>

            <a class="nav-link {{ request()->routeIs('trip.*') ? 'active' : '' }}" href="{{ route('trip.report') }}">
                <i class="bi bi-list-check"></i> Trip Sheet
            </a>

            <a class="nav-link {{ request()->routeIs('collections.*') ? 'active' : '' }}" href="{{ route('collections.index') }}">
                <i class="bi bi-list-check"></i> Collection
            </a>

        </div>
    </div>
</nav>


<div class="mt-4 ms-0 w-100">
    @yield('content')
</div>


@stack('scripts')
</body>
</html>
