@extends('layouts.master')

@section('title', 'Manual Bill Items Report')

@section('content')
<div class="container py-4">

    {{-- Filter --}}
    <div class="card mb-4 shadow-sm hide-print">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Start Date</label>
                    <input type="date" name="start_date" class="form-control"
                           value="{{ request('start_date') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">End Date</label>
                    <input type="date" name="end_date" class="form-control"
                           value="{{ request('end_date') }}">
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-primary w-100">Apply</button>
                    <a href="{{ route('manualStocks') }}"
                       class="btn btn-outline-secondary w-100">Reset</a>
                </div>

            </form>
        </div>
    </div>

    {{-- Table --}}
    @if($filtersApplied)
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center hide-print">
                <div class="fw-semibold">Manual Bill Items</div>
                <button onclick="window.print()" class="btn btn-info">Print</button>
            </div>

            <div class="card-body p-0">
                @if($items->count())
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Bill Date</th>
                                    <th>Bill No</th>
                                    <th>Product</th>
                                    <th class="text-end">Box</th>
                                    <th class="text-end">PCS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $i => $item)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>
                                            {{ optional($item->partySale->bill_date)->format('d M Y') }}
                                        </td>
                                        <td>{{ $item->partySale->bill_no ?? '-' }}</td>
                                        <td>{{ $item->product->name ?? '-' }}</td>
                                        <td class="text-end">{{ $item->box }}</td>
                                        <td class="text-end">{{ $item->pcs }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-4 text-center text-muted">
                        No records found for selected date range.
                    </div>
                @endif
            </div>
        </div>
    @endif

</div>
@endsection
