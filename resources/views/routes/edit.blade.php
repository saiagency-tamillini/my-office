@extends('layouts.master')

@section('title', 'Edit Route')

@section('content')

<h3>Edit Route</h3>

<form method="POST" action="{{ route('routes.update', $route->id) }}">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label class="form-label">Route Name</label>
        <input type="text"
               name="name"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $route->name) }}">

        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <button class="btn btn-primary">Update</button>
    <a href="{{ route('routes.index') }}" class="btn btn-secondary">Cancel</a>
</form>

@endsection
