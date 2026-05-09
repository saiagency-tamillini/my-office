@extends('layouts.master')

@section('content')

@php
use Illuminate\Support\Str;
@endphp

<div class="container">

    <h3 class="mb-4">Bill Number Report</h3>

    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-body">

            <form method="GET" action="{{ route('bill.number.report') }}">

                <div class="row align-items-end">

                    <div class="col-md-3">
                        <label class="fw-bold">From Date</label>

                        <input type="date"
                               name="from_date"
                               value="{{ request('from_date') }}"
                               class="form-control"
                               required>
                    </div>

                    <div class="col-md-3">
                        <label class="fw-bold">To Date</label>

                        <input type="date"
                               name="to_date"
                               value="{{ request('to_date') }}"
                               class="form-control">
                    </div>

                    <div class="col-md-3">

                        <button class="btn btn-primary">
                            Filter
                        </button>

                        <a href="{{ route('bill.number.report') }}"
                           class="btn btn-secondary">
                            Reset
                        </a>

                    </div>

                </div>

            </form>

        </div>
    </div>

    <!-- RESULT -->
    @if($billSummary->isNotEmpty())

        <div class="card">

            <div class="card-header fw-bold">
                Bill Summary
            </div>

            <div class="card-body p-0">

                <table class="table table-bordered text-center mb-0">

                    <thead class="table-light">
                        <tr>
                            <th>Series</th>
                            <th>Start Bill</th>
                            <th>End Bill</th>
                            <th>Total Count</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($billSummary as $index => $item)

                            <tr>

                                <td>{{ $item->prefix }}</td>
                                <td>{{ $item->start_bill }}</td>
                                <td>{{ $item->end_bill }}</td>
                                <td>{{ $item->total_count }}</td>

                                <td>

                                    <button class="btn btn-sm btn-primary"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#details{{ $index }}">

                                        View List

                                    </button>

                                </td>

                            </tr>

                            <!-- DETAILS -->
                            <tr class="collapse" id="details{{ $index }}">

                                <td colspan="5">

                                    @php

                                        $filteredBills = $billDetails->filter(function ($bill) use ($item) {
                                            return Str::startsWith($bill->bill_no, $item->prefix);
                                        });

                                        // SORTING
                                        $sort = request('sort', 'bill_no');
                                        $direction = request('direction', 'asc');

                                        $filteredBills = $direction == 'asc'
                                            ? $filteredBills->sortBy($sort)
                                            : $filteredBills->sortByDesc($sort);

                                    @endphp

                                    <table class="table table-bordered mb-0">

                                        <thead class="table-secondary">

                                            <tr>

                                                <th>

                                                    <a href="?from_date={{ request('from_date') }}
                                                            &to_date={{ request('to_date') }}
                                                            &sort=bill_no
                                                            &direction={{ request('direction') == 'asc' ? 'desc' : 'asc' }}"
                                                       class="text-decoration-none text-dark">

                                                        Bill Number

                                                        @if(request('sort') == 'bill_no')
                                                            {!! request('direction') == 'asc' ? '↑' : '↓' !!}
                                                        @endif

                                                    </a>

                                                </th>

                                                <th>

                                                    <a href="?from_date={{ request('from_date') }}
                                                            &to_date={{ request('to_date') }}
                                                            &sort=customer_name
                                                            &direction={{ request('direction') == 'asc' ? 'desc' : 'asc' }}"
                                                       class="text-decoration-none text-dark">

                                                        Customer Name

                                                        @if(request('sort') == 'customer_name')
                                                            {!! request('direction') == 'asc' ? '↑' : '↓' !!}
                                                        @endif

                                                    </a>

                                                </th>

                                                <th>

                                                    <a href="?from_date={{ request('from_date') }}
                                                            &to_date={{ request('to_date') }}
                                                            &sort=payment_status
                                                            &direction={{ request('direction') == 'asc' ? 'desc' : 'asc' }}"
                                                       class="text-decoration-none text-dark">

                                                        Status

                                                        @if(request('sort') == 'payment_status')
                                                            {!! request('direction') == 'asc' ? '↑' : '↓' !!}
                                                        @endif

                                                    </a>

                                                </th>

                                            </tr>

                                        </thead>

                                        <tbody>

                                            @forelse($filteredBills as $bill)

                                                <tr>

                                                    <td>
                                                        {{ $bill->bill_no }}
                                                    </td>

                                                    <td>
                                                        {{ $bill->customer_name ?? '-' }}
                                                    </td>

                                                    <td>

                                                        @if(strtolower($bill->payment_status) == 'complete')

                                                            <span class="badge bg-success">
                                                                {{ $bill->payment_status }}
                                                            </span>

                                                        @else

                                                            <span class="badge bg-warning text-dark">
                                                                {{ $bill->payment_status }}
                                                            </span>

                                                        @endif

                                                    </td>

                                                </tr>

                                            @empty

                                                <tr>
                                                    <td colspan="3">
                                                        No bills found
                                                    </td>
                                                </tr>

                                            @endforelse

                                        </tbody>

                                    </table>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>

    @elseif(request()->filled('from_date'))

        <div class="alert alert-warning">
            No bill data found for selected date.
        </div>

    @endif

</div>

@endsection