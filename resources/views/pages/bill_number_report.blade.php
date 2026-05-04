@extends('layouts.master')

@section('content')
<div class="container">

    <h3 class="mb-4">Bill Number Report</h3>

    <!-- Filter -->
    <div class="card mb-3">
        <div class="card-body">

            <form method="GET" action="{{ route('bill.number.report') }}">

                <div class="row align-items-end">

                    <div class="col-md-3">
                        <label class="fw-bold">From Date</label>
                        <input type="date" name="from_date"
                               value="{{ request('from_date') }}"
                               class="form-control" required>
                    </div>

                    <div class="col-md-3">
                        <label class="fw-bold">To Date</label>
                        <input type="date" name="to_date"
                               value="{{ request('to_date') }}"
                               class="form-control">
                    </div>

                    <div class="col-md-3">
                        <button class="btn btn-primary">
                            <i class="bi bi-search"></i> Filter
                        </button>

                        <a href="{{ route('bill.number.report') }}" class="btn btn-secondary">
                            Reset
                        </a>
                    </div>

                </div>

            </form>

        </div>
    </div>

    <!-- Result -->
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
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($billSummary as $item)
                            <tr>
                                <td>{{ $item->prefix }}</td>
                                <td>{{ $item->start_bill }}</td>
                                <td>{{ $item->end_bill }}</td>
                                <td>{{ $item->total_count }}</td>
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