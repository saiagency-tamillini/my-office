<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Sai Agency')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
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

            <a class="navbar-brand fw-semibold" href="{{ route('home') }}">Sai Agency</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                    aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                <div class="d-flex w-100 align-items-center justify-content-between">

                    {{-- LEFT MENU --}}
                    <div class="navbar-nav">
                        {{-- <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
                            <i class="bi bi-house-door-fill"></i>
                        </a> --}}

                        @auth
                            @if(is_admin())
                                <a class="nav-link {{ request()->routeIs('salesman') ? 'active' : '' }}" href="{{ route('salesman') }}">
                                    <i class="bi bi-person-badge"></i> Sales Man
                                </a>

                                <a class="nav-link {{ request()->routeIs('collections.*') ? 'active' : '' }}" href="{{ route('collections.index') }}">
                                    <i class="bi bi-cash-coin"></i> Daily Collections
                                </a>

                                <a class="nav-link {{ request()->routeIs('manualStocks') ? 'active' : '' }}" href="{{ route('manualStocks') }}">
                                    <i class="bi bi-box-seam"></i> Manual Products list
                                </a>

                                <div class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle {{ request()->routeIs('beats.*') || request()->routeIs('routes.*') || request()->routeIs('customers.*') || request()->routeIs('products.*') ? 'active' : '' }}"
                                    href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-people-fill"></i> Customers
                                    </a>

                                    <ul class="dropdown-menu dropdown-menu-dark">
                                        <li>
                                            <a class="dropdown-item {{ request()->routeIs('beats.*') ? 'active' : '' }}"
                                            href="{{ route('beats.index') }}">
                                                <i class="bi bi-pin-map"></i> Beats
                                            </a>
                                        </li>

                                        <li>
                                            <a class="dropdown-item {{ request()->routeIs('routes.*') ? 'active' : '' }}"
                                            href="{{ route('routes.index') }}">
                                                <i class="bi bi-signpost-2"></i> Routes
                                            </a>
                                        </li>

                                        <li>
                                            <a class="dropdown-item {{ request()->routeIs('customers.*') ? 'active' : '' }}"
                                            href="{{ route('customers.index') }}">
                                                <i class="bi bi-person-vcard"></i> Party Masters
                                            </a>
                                        </li>

                                        <li>
                                            <a class="dropdown-item {{ request()->routeIs('products.*') ? 'active' : '' }}"
                                            href="{{ route('products.index') }}">
                                                <i class="bi bi-tags"></i> Products
                                            </a>
                                        </li>
                                        @if(is_super_admin())
                                            <li>
                                                <a class="dropdown-item {{ request()->routeIs('users.*') ? 'active' : '' }}"
                                                href="{{ route('users.index') }}">
                                                    <i class="bi bi-person-gear"></i> Users
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>

                                <div class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle
                                        {{ request()->routeIs('fileUpload') || request()->routeIs('party-sales.*') || request()->routeIs('reportTable') || request()->routeIs('trip.*') ? 'active' : '' }}"
                                    href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-bar-chart-fill"></i> MIS
                                    </a>

                                    <ul class="dropdown-menu dropdown-menu-dark">
                                        <li>
                                            <a class="dropdown-item {{ request()->routeIs('fileUpload') ? 'active' : '' }}"
                                            href="{{ route('fileUpload') }}">
                                                <i class="bi bi-upload"></i> File Upload
                                            </a>
                                        </li>

                                        <li>
                                            <a class="dropdown-item {{ request()->routeIs('party-sales.*') ? 'active' : '' }}"
                                            href="{{ route('party-sales.index') }}">
                                                <i class="bi bi-receipt"></i> Party Sales
                                            </a>
                                        </li>

                                        <li>
                                            <a class="dropdown-item {{ request()->routeIs('reportTable') ? 'active' : '' }}"
                                            href="{{ route('reportTable') }}">
                                                <i class="bi bi-clipboard-data"></i> Salesman Collections
                                            </a>
                                        </li>

                                        <li>
                                            <a class="dropdown-item {{ request()->routeIs('trip.report*') ? 'active' : '' }}"
                                            href="{{ route('trip.report') }}">
                                                <i class="bi bi-truck"></i> Trip Sheet
                                            </a>
                                        </li>

                                        <li>
                                            <a class="dropdown-item {{ request()->routeIs('trip.details*') ? 'active' : '' }}"
                                            href="{{ route('trip.details') }}">
                                                <i class="bi bi-journal-text"></i> Trip Details
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            @endif
                        @endauth
                    </div>

                    {{-- RIGHT ACTIONS --}}
                    <div class="d-flex align-items-center gap-2 py-2 py-lg-0">

                        @guest
                            <a class="btn btn-sm btn-outline-light d-flex align-items-center gap-1"
                            href="{{ route('home') }}">
                                <i class="bi bi-box-arrow-in-right"></i>
                                <span>Login</span>
                            </a>
                            <a class="btn btn-sm btn-info d-flex align-items-center gap-1"
                            href="{{ route('register.show') }}">
                                <i class="bi bi-person-plus-fill"></i>
                                <span>Sign Up</span>
                            </a>
                        @endguest

                        @auth
                            <span class="badge rounded-pill bg-light text-dark border px-3 py-2 d-flex align-items-center">
                                <i class="bi bi-person-circle me-2"></i>
                                <span class="fw-semibold">{{ auth()->user()->name }}</span>
                            </span>
                            {{-- @if(is_admin())
                                <a class="btn btn-sm btn-outline-info d-flex align-items-center gap-1"
                                href="{{ route('register.show') }}">
                                    <i class="bi bi-person-plus-fill"></i>
                                    <span>Create User</span>
                                </a>
                            @endif --}}
                            <form method="POST" action="{{ route('logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger d-flex align-items-center gap-1">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <span>Logout</span>
                                </button>
                            </form>
                        @endauth
                    </div>

                </div>
            </div>
        </div>
    </nav>

    <div class="mt-4 ms-0 w-100">
        @yield('content')
    </div>

    @include('partials.balance_calculator')

    @stack('scripts')
    <script src="{{ asset('js/balance-calculator.js') }}"></script>
</body>
</html>
