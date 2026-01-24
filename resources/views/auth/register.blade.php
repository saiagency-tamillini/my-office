@extends('layouts.master')

@section('title', 'Sign Up - Sai Agency')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h4 class="mb-1 fw-bold">Create Account</h4>
                    <p class="text-muted mb-4">Sign up to access Sai Agency.</p>

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

                    <form method="POST" action="{{ route('register.perform') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text"
                                       name="name"
                                       value="{{ old('name') }}"
                                       class="form-control"
                                       placeholder="Enter full name"
                                       required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username</label>
                                <input type="text"
                                       name="username"
                                       value="{{ old('username') }}"
                                       class="form-control"
                                       placeholder="Choose a username"
                                       required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   class="form-control"
                                   placeholder="Enter email"
                                   required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password</label>
                                <input type="password"
                                       name="password"
                                       class="form-control"
                                       placeholder="Create password"
                                       required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password"
                                       name="password_confirmation"
                                       class="form-control"
                                       placeholder="Confirm password"
                                       required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-dark w-100">
                            <i class="bi bi-person-plus-fill me-1"></i> Sign Up
                        </button>

                        <div class="text-center mt-3">
                            <span class="text-muted">Already have an account?</span>
                            <a href="{{ route('home') }}" class="text-decoration-none fw-semibold">
                                Go to Login
                            </a>
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
