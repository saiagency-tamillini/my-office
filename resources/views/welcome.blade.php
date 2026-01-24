@extends('layouts.master')

@section('title', 'Login - Sai Agency')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">

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
                            <label class="form-label">Email or Username</label>
                            <input type="text"
                                   name="username"
                                   value="{{ old('username') }}"
                                   class="form-control"
                                   placeholder="Enter email or username"
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

                        {{-- <div class="text-center mt-3">
                            <span class="text-muted">New user?</span>
                            <a href="{{ route('register.show') }}" class="text-decoration-none fw-semibold">
                                Create an account
                            </a>
                        </div> --}}
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
