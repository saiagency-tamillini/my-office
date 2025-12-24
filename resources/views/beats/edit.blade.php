@extends('layouts.master')

@section('title', 'Edit Beat')

@section('content')

<h3>Edit Beat</h3>

<form method="POST" action="{{ route('beats.update', $beat->id) }}">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label class="form-label">Beat Name</label>
        <input type="text"
               name="name"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $beat->name) }}">

        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label class="form-label">Salesman</label>
        <input type="text"
               name="salesman"
               class="form-control @error('salesman') is-invalid @enderror"
               value="{{ old('salesman', $beat->salesman) }}">

        @error('salesman')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <button class="btn btn-primary">Update</button>
    <a href="{{ route('beats.index') }}" class="btn btn-secondary">Cancel</a>
</form>

@endsection
