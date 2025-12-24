@extends('layouts.master')

@section('title', 'Beats List')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Beats List</h3>
    <a href="{{ route('beats.create') }}" class="btn btn-primary">
        + Add Beat
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
            <th>Beat Name</th>
            <th>Salesman</th>
            <th width="180">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($beats as $beat)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $beat->name }}</td>
                <td>{{ $beat->salesman }}</td>
                <td>
                    <a href="{{ route('beats.edit', $beat->id) }}" class="btn btn-sm btn-warning">
                        Edit
                    </a>

                    <form action="{{ route('beats.destroy', $beat->id) }}"
                          method="POST"
                          class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger"
                                onclick="return confirm('Delete this beat?')">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center">No beats found</td>
            </tr>
        @endforelse
    </tbody>
</table>

@endsection
