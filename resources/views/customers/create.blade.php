@extends('layouts.master')

@section('title', 'Add Customer')

@section('content')

<div class="mb-3">
    <h3>Add Customer</h3>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('customers.store') }}" method="POST">
    @csrf

    <div class="mb-3">
        <label for="name" class="form-label">Customer Name</label>
        <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
    </div>

    <div class="mb-3">
        <label for="beat_id" class="form-label">Beat</label>
        <select name="beat_id" id="beat_id" class="form-select" required>
            <option value="">Select Beat</option>
            @foreach($beats as $beat)
                <option value="{{ $beat->id }}" {{ old('beat_id') == $beat->id ? 'selected' : '' }}>
                    {{ $beat->name }}
                </option>
            @endforeach
        </select>
    </div>

    <button type="submit" class="btn btn-success">Save</button>
    <a href="{{ route('customers.index') }}" class="btn btn-secondary">Back</a>
</form>

@endsection
