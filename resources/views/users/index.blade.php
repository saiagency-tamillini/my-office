@extends('layouts.master')

@section('title', 'Users')

@section('content')
<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0 fw-bold">Manage Users</h4>
    </div>

    {{-- Flash --}}
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

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

    <div class="row g-3">
        {{-- Create User --}}
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white">
                    <span class="fw-semibold">Create User</span>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('users.store') }}">
                        @csrf

                        <div class="mb-2">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" value="{{ old('username') }}" class="form-control" required>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Email (optional)</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-control">
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Role</label>
                            <select name="role_id" class="form-select" required>
                                <option value="">Select role</option>
                                @foreach($roles as $r)
                                    <option value="{{ $r->id }}" {{ old('role_id') == $r->id ? 'selected' : '' }}>
                                        {{ $r->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>

                        <button class="btn btn-success w-100">
                            <i class="bi bi-person-plus-fill me-1"></i> Create User
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Users Table --}}
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <span class="fw-semibold">Users</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $i => $u)
                                    <tr>
                                        <td>{{ $users->firstItem() + $i }}</td>
                                        <td class="fw-semibold">{{ $u->name }}</td>
                                        <td>{{ $u->username }}</td>
                                        <td>{{ $u->email ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                {{ $u->role?->name ?? 'No role' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('users.edit', $u->id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </a>

                                            <form action="{{ route('users.destroy', $u->id) }}"
                                                  method="POST"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Delete this user?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No users found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="p-3">
                        {{ $users->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>

</div>
@endsection
