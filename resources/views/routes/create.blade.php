@extends('layouts.master')

@section('title', 'Add Route')

@section('content')

<h3>Add Route</h3>

<form method="POST" action="{{ route('routes.store') }}">
    @csrf

    <div class="mb-3">
        <label class="form-label">Route Name</label>
        <input type="text"
               name="name"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name') }}">

        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <button class="btn btn-success">Save</button>
    <a href="{{ route('routes.index') }}" class="btn btn-secondary">Cancel</a>
</form>

@endsection
