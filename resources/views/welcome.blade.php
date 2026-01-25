@extends('layouts.master')

@section('title', 'Login - Sai Agency')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">

            {{-- If user is logged in --}}
            @auth
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h4 class="mb-1 fw-bold">
                            Welcome, {{ auth()->user()->name ?? auth()->user()->username ?? 'User' }} 👋
                        </h4>
                        <p class="text-muted mb-4">
                            You are already logged in to <strong>Sai Agency</strong>.
                        </p>
                        @if(!is_admin())
                            <div class="alert alert-success">
                                <div class="fw-semibold text-center">Login successful!</div>
                                <div class="alert alert-info">
                                    <div class="fw-semibold mb-1">Need access or help?</div>
                                    <div class="small">
                                        Please contact the site admin <strong>Anand</strong>.
                                    </div>

                                    <hr class="my-2">

                                    <div class="d-flex flex-column gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-dark">Mobile</span>
                                            <a href="tel:+91XXXXXXXXXX" class="text-decoration-none">+91 XXXXXXXXXX</a>
                                        </div>

                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-dark">Email</span>
                                            <a href="mailto:anand@example.com" class="text-decoration-none">anand@example.com</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="d-flex gap-2">
                            <form method="POST" action="{{ route('logout') }}" class="w-50">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger w-100">
                                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            {{-- If user is NOT logged in --}}
            @else
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h4 class="mb-1 fw-bold">Login</h4>
                        <p class="text-muted mb-4">Sign in to continue to Sai Agency.</p>

                        {{-- Errors --}}
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <div class="fw-semibold mb-1">Please fix the below errors:</div>
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Status --}}
                        @if (session('status'))
                            <div class="alert alert-success">{{ session('status') }}</div>
                        @endif

                        <form method="POST" action="{{ route('login.perform') }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text"
                                       name="username"
                                       value="{{ old('username') }}"
                                       class="form-control"
                                       placeholder="Enter username"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password"
                                       name="password"
                                       class="form-control"
                                       placeholder="Enter password"
                                       required>
                            </div>

                            <button type="submit" class="btn btn-dark w-100">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Login
                            </button>

                            <div class="text-center mt-3">
                                <span class="text-muted">New user?</span>
                                <a href="{{ route('register.show') }}" class="text-decoration-none fw-semibold">
                                    Create an account
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            @endauth

        </div>
    </div>
</div>
@endsection
