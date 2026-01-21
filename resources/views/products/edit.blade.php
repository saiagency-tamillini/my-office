@extends('layouts.master')

@section('title', 'Edit Product')

@section('content')

<div class="card">
    <div class="card-header">
        <h4>Edit Product</h4>
    </div>

    <div class="card-body">
        <form action="{{ route('products.update', $product->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Product Name</label>
                <input type="text"
                       name="name"
                       class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $product->name) }}">

                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="text-end">
                <a href="{{ route('products.index') }}" class="btn btn-secondary">
                    Back
                </a>
                <button type="submit" class="btn btn-warning">
                    Update
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
