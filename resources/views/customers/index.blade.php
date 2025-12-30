@extends('layouts.master')

@section('title', 'Customers List')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Customers List</h3>
    <a href="{{ route('customers.create') }}" class="btn btn-primary">
        + Add Customer
    </a>
</div>
@php
    function sortOrder($column, $sortBy, $sortOrder) {
        return ($sortBy === $column && $sortOrder === 'asc') ? 'desc' : 'asc';
    }
@endphp

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<table class="table table-bordered table-hover">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>
                <a href="{{ route('customers.index', [
                    'sort_by' => 'name',
                    'sort_order' => sortOrder('name', $sortBy, $sortOrder)
                ]) }}" class="text-white text-decoration-none">
                    Name
                    @if($sortBy === 'name')
                        {{ $sortOrder === 'asc' ? '▲' : '▼' }}
                    @endif
                </a>
            </th>
            <th>
                <a href="{{ route('customers.index', [
                    'sort_by' => 'beat_id',
                    'sort_order' => sortOrder('beat_id', $sortBy, $sortOrder)
                    ]) }}" class="text-white text-decoration-none">
                    Beat
                </a>
            </th>
            <th>Outstanding</th>
            <th width="180">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($customers as $customer)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $customer->name }}</td>
                <td>{{ $customer->beat->name ?? '-' }}</td>
                <td>
                    @if($customer->outstanding > 0)
                        <span class="text-danger fw-bold">
                            ₹ {{ number_format($customer->outstanding, 2) }}
                        </span>
                    @else
                        <span class="text-success">
                            ₹ 0.00
                        </span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-sm btn-warning">
                        Edit
                    </a>

                    <form action="{{ route('customers.destroy', $customer->id) }}"
                          method="POST"
                          class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger"
                                onclick="return confirm('Delete this customer?')">
                            Delete
                        </button>
                    </form>

                    <a href="{{ route('customers.transactions', $customer->id) }}"
                        class="btn btn-sm btn-primary mt-1">
                        View Transactions
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center">No customers found</td>
            </tr>
        @endforelse
    </tbody>
</table>

@endsection
