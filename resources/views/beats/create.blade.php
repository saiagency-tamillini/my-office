@extends('layouts.master')

@section('title', 'Add Beat')

@section('content')

<h3>Add Beat</h3>

<form method="POST" action="{{ route('beats.store') }}">
    @csrf

    <div class="mb-3">
        <label class="form-label">Beat Name</label>
        <input type="text"
               name="name"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name') }}">

        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label class="form-label">Salesman</label>
        <input type="text"
               name="salesman"
               class="form-control @error('salesman') is-invalid @enderror"
               value="{{ old('salesman') }}">

        @error('salesman')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <button class="btn btn-success">Save</button>
    <a href="{{ route('beats.index') }}" class="btn btn-secondary">Cancel</a>
</form>

@endsection
