@extends('layouts.master')

@section('title', 'Product List')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Product List</h3>
    <a href="{{ route('products.create') }}" class="btn btn-primary">
        + Add Product
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
            <th>Product Name</th>
            <th>Box Amount</th>
            <th>Piece Amount</th>
            <th width="180">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($products as $product)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $product->name }}</td>
                <td>{{ $product->box_amount }}</td>
                <td>{{ $product->piece_amount }}</td>
                <td>
                    <a href="{{ route('products.edit', $product->id) }}" class="btn btn-sm btn-warning">
                        Edit
                    </a>

                    <form action="{{ route('products.destroy', $product->id) }}"
                          method="POST"
                          class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger"
                                onclick="return confirm('Delete this product?')">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="text-center">No products found</td>
            </tr>
        @endforelse
    </tbody>
</table>

@endsection
