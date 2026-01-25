@extends('layouts.master')

@section('title', 'Edit User')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">

            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">
                    <span class="fw-semibold">Edit User</span>
                </div>

                <div class="card-body p-4">

                    {{-- Errors --}}
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <div class="fw-semibold mb-1">Please fix the below errors:</div>
                            <ul class="mb-0">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('users.update', $user->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-2">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" value="{{ old('username', $user->username) }}" class="form-control" required>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Email (optional)</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control">
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Role</label>
                            <select name="role_id" class="form-select" required>
                                @foreach($roles as $r)
                                    <option value="{{ $r->id }}" {{ old('role_id', $user->role_id) == $r->id ? 'selected' : '' }}>
                                        {{ $r->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">New Password (optional)</label>
                            <input type="password" name="password" class="form-control" placeholder="Leave blank to keep same password">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="password_confirmation" class="form-control">
                        </div>

                        <div class="d-flex gap-2">
                            <button class="btn btn-primary w-50">
                                <i class="bi bi-save me-1"></i> Update
                            </button>
                            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary w-50">
                                Back
                            </a>
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
