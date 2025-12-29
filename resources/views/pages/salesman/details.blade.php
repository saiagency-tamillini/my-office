@extends('layouts.master')

@section('content')
<div class="container">
    <h3>Payment Entries</h3>
    
    @foreach($paymentEntries as $billNo => $entries)
        @php
            $isPaid = optional($entries->first())->is_paid;
            $cardClass = $isPaid ? 'bg-success text-white' : 'bg-warning';
        @endphp
        <div class="card mt-3 {{ $cardClass }}">
            <div class="card-header">
                <strong>Bill No:</strong> {{ $billNo }}
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Amount Received</th>
                            <th>Balance</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entries as $entry)
                            <tr>
                                <td>{{ $entry->customer->name }}</td>
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
    @endforeach
</div>
@endsection
