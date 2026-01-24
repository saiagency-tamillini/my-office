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
            <div class="mb-3">
                <label class="form-label">Box Amount</label>
                <input type="number"
                    name="box_amount"
                    class="form-control @error('box_amount') is-invalid @enderror"
                    value="{{ old('box_amount', $product->box_amount) }}"
                    step="0.01"
                    min="0">

                @error('box_amount')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Piece Amount</label>
                <input type="number"
                    name="piece_amount"
                    class="form-control @error('piece_amount') is-invalid @enderror"
                    value="{{ old('piece_amount', $product->piece_amount) }}"
                    step="0.01"
                    min="0">

                @error('piece_amount')
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
