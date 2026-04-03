@extends('layouts.master')

@section('title', 'Routes List')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Routes List</h3>
    <a href="{{ route('routes.create') }}" class="btn btn-primary">
        + Add Route
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<table class="table table-bordered table-hover">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>Route Name</th>
            <th width="180">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($routes as $route)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $route->name }}</td>
                <td>
                    <a href="{{ route('routes.edit', $route->id) }}" class="btn btn-sm btn-warning">
                        Edit
                    </a>

                    <form action="{{ route('routes.destroy', $route->id) }}"
                          method="POST"
                          class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger"
                                onclick="return confirm('Delete this route?')">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="text-center">No routes found</td>
            </tr>
        @endforelse
    </tbody>
</table>

@endsection
