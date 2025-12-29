@extends('layouts.master')

@section('content')
<div class="container">
    <h3>
        Transactions for {{ $customer->name }}
        <a href="{{ route('customers.index') }}" class="btn btn-sm btn-secondary float-end">
            Back
        </a>
    </h3>

    @forelse($transactions as $billNo => $entries)
        <div class="card mt-3">
            <div class="card-header">
                <strong>Bill No:</strong> {{ $billNo }}
            </div>

            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Received</th>
                            <th>Balance</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entries as $entry)
                            <tr>
                                <td>{{ $entry->payment_date }}</td>
                                <td>{{ $entry->amount }}</td>
                                <td>{{ $entry->amount_received }}</td>
                                <td>{{ $entry->balance }}</td>
                                <td>{{ $entry->remarks }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <p class="text-muted mt-3">No transactions found.</p>
    @endforelse
</div>
@endsection
