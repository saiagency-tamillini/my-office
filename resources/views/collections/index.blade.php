@extends('layouts.master')
<link rel="stylesheet" href="{{ asset('css/collections.css') }}">
@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3 hide-print">
            <div>
                <h4 class="mb-0">Collections</h4>
                <small class="text-muted">Select salesman and date to view party payment entries</small>
            </div>
        </div>
        <div class="card shadow-sm border-0 mb-4 hide-print">
            <div class="card-body">
                <form id="filterForm" method="GET" action="{{ route('collections.index') }}" class="row g-3 align-items-end">

                    <div class="col-12 col-md-2">
                        <label class="form-label fw-semibold">Salesman</label>
                        <select name="salesman" class="form-select">
                            <option value="">Select Salesman</option>
                            @foreach($salesmen as $salesman)
                                <option value="{{ $salesman }}" {{ request('salesman') == $salesman ? 'selected' : '' }}>
                                    {{ $salesman }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    {{-- <div class="col-12 col-md-2">
                        <label class="form-label fw-semibold">Beat</label>
                        <select name="beat_id" class="form-select">
                            <option value="">Select Beat</option>
                            @foreach($beats as $beat)
                                <option value="{{ $beat->id }}"
                                    {{ request('beat_id') == $beat->id ? 'selected' : '' }}>
                                    {{ $beat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div> --}}
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">Beat:</label>
                        <div class="d-flex gap-2">
                            <div id="beat-container">
                                <div class="d-flex align-items-center gap-2 beat-row">
                                    <select name="beat_ids[]" class="form-select beat-select">
                                        <option value="">-- Select Beat --</option>
                                        @foreach($beats as $beat)
                                            <option value="{{ $beat->id }}"
                                                {{ in_array($beat->id, (array)request('beat_ids')) ? 'selected' : '' }}>
                                                {{ $beat->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-beat d-none">
                                        ❌
                                    </button>
                                </div>
                            </div>

                            <button type="button" class="btn btn-sm btn-outline-primary h-fit" id="add-beat">
                                + Add More
                            </button>
                        </div>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label fw-semibold">Payment Date</label>
                        <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                    </div>
                    <div class="col-12 col-md-1">
                        <label class="form-label fw-semibold">Show</label>
                        <select name="per_page" class="form-select" id="perPage">
                            @foreach([10,25,50,100,200,500] as $n)
                                <option value="{{ $n }}" {{ request('per_page', 25) == $n ? 'selected' : '' }}>
                                    {{ $n }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">Apply</button>
                        <a href="{{ route('collections.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        @if($filtersApplied)
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 hide-print">
                    <div class="fw-semibold">Payment Entries</div>
                    @if($entries && $entries->count())
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <a href="{{ route('collections.download', request()->all()) }}" class="btn btn-success">
                                Download Excel
                            </a>
                            <button type="button" class="btn btn-info" onclick="printCollections()">
                                Print
                            </button>
                        </div>
                    @endif
                    <div class="text-muted small d-flex flex-wrap gap-2">
                        @if(request('salesman'))
                            <span class="badge bg-light text-dark border">Salesman: {{ request('salesman') }}</span>
                        @endif
                        @if(request('date'))
                            <span class="badge bg-light text-dark border">Date: {{ request('date') }}</span>
                        @endif
                        <span class="badge bg-light text-dark border">Per page: {{ request('per_page', 25) }}</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($entries && $entries->count())
                        <div class="table-responsive">
                            <div id="printArea">
                                <table class="table table-hover table-striped align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Payment Date</th>
                                            <th>Salesman</th>
                                            <th>Customer</th>
                                            <th>Bill No</th>
                                            <th class="text-end">Amount</th>
                                            <th class="text-end">CD</th>
                                            <th class="text-end">Return</th>
                                            <th class="text-end">Online</th>
                                            <th class="text-end">Received</th>
                                            <th class="text-end">Balance</th>
                                            <th class="hide-print">Remarks</th>
                                            <th class="hide-print">Status</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @php
                                            $totalAmount = 0;
                                            $totalReceived = 0;
                                            $totalBalance = 0;
                                        @endphp

                                        @foreach($entries as $i => $entry)
                                            @php
                                                $totalAmount += (float) ($entry->amount ?? 0);
                                                $totalReceived += (float) ($entry->amount_received ?? 0);
                                                $totalBalance += (float) ($entry->balance ?? 0);

                                                $salesmanName = optional(optional(optional($entry->partySale)->beat))->salesman;
                                                $customerName = optional($entry->customer)->name
                                                    ?? optional(optional($entry->partySale)->customer)->name
                                                    ?? '-';

                                                $statusText = $entry->status ?? 'N/A';
                                                $status = strtolower((string) $statusText);
                                                $badge = match(true) {
                                                    in_array($status, ['paid','success','completed']) => 'success',
                                                    in_array($status, ['pending','open']) => 'warning',
                                                    in_array($status, ['failed','cancelled','canceled']) => 'danger',
                                                    default => 'secondary',
                                                };
                                            @endphp

                                            <tr>
                                                <td class="text-muted">{{ $entries->firstItem() + $i }}</td>
                                                <td class="fw-semibold">{{ optional($entry->payment_date)->format('d M Y') ?? '-' }}</td>
                                                <td>{{ $salesmanName ?? '-' }}</td>
                                                <td class="text-truncate" style="max-width: 220px;">{{ $customerName }}</td>
                                                <td>{{ $entry->bill_no ?? '-' }}</td>

                                                <td class="text-end">{{ number_format((float)($entry->amount ?? 0), 2) }}</td>
                                                <td class="text-end">{{ number_format((float)($entry->cd ?? 0), 2) }}</td>
                                                <td class="text-end">{{ number_format((float)($entry->product_return ?? 0), 2) }}</td>
                                                <td class="text-end">{{ number_format((float)($entry->online_payment ?? 0), 2) }}</td>
                                                <td class="text-end fw-semibold">{{ number_format((float)($entry->amount_received ?? 0), 2) }}</td>
                                                <td class="text-end fw-semibold">{{ number_format((float)($entry->balance ?? 0), 2) }}</td>

                                                <td class="text-truncate hide-print" style="max-width: 240px;">{{ $entry->remarks ?? '-' }}</td>

                                                <td class="hide-print">
                                                    <span class="badge bg-{{ $badge }}">{{ $statusText }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>

                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="5" class="text-end">Totals:</th>
                                            <th class="text-end">{{ number_format($totalAmount, 2) }}</th>
                                            <th colspan="3"></th>
                                            <th class="text-end">{{ number_format($totalReceived, 2) }}</th>
                                            <th class="text-end">{{ number_format($totalBalance, 2) }}</th>
                                            <th class="hide-print"colspan="2"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <div class="p-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                            <div class="text-muted small">
                                Showing <strong>{{ $entries->firstItem() }}</strong> to <strong>{{ $entries->lastItem() }}</strong>
                                of <strong>{{ $entries->total() }}</strong> entries
                            </div>
                            <div>
                                {{ $entries->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    @else
                        <div class="p-4 text-center">
                            <div class="text-muted">No records found for selected filters.</div>
                        </div>
                    @endif
                </div>
            </div>

        @endif
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('filterForm');
            const perPage = document.getElementById('perPage');
            if (!form || !perPage) return;

            perPage.addEventListener('change', function () {
                const url = new URL(window.location.href);
                url.searchParams.delete('page');
                const formData = new FormData(form);
                const params = new URLSearchParams(formData);
                window.location.href = url.pathname + '?' + params.toString();
            });
        });
        function printCollections() {
            window.print();
        }
        const beats = @json($beats);
        const beatContainer = document.getElementById('beat-container');
        const addBeatBtn = document.getElementById('add-beat');

        function getSelectedBeats() {
            return Array.from(document.querySelectorAll('.beat-select'))
                .map(select => select.value)
                .filter(val => val !== "");
        }

        function refreshOptions() {
            const selected = getSelectedBeats();

            document.querySelectorAll('.beat-select').forEach(select => {
                const current = select.value;
                select.innerHTML = '<option value="">-- Select Beat --</option>';

                beats.forEach(beat => {
                    if (!selected.includes(String(beat.id)) || String(beat.id) === current) {
                        const option = document.createElement('option');
                        option.value = beat.id;
                        option.text = beat.name;
                        option.selected = String(beat.id) === current;
                        select.appendChild(option);
                    }
                });
            });
        }

        function createBeatRow() {
            const row = document.createElement('div');
            row.className = 'd-flex align-items-center gap-2 mt-2 beat-row';

            row.innerHTML = `
                <select name="beat_ids[]" class="form-select beat-select">
                    <option value="">-- Select Beat --</option>
                </select>
                <button type="button" class="btn btn-outline-danger btn-sm remove-beat">
                    ❌
                </button>
            `;

            beatContainer.appendChild(row);
            refreshOptions();
        }

        addBeatBtn.addEventListener('click', () => {
            createBeatRow();
        });

        // Change event → refresh options
        beatContainer.addEventListener('change', function (e) {
            if (e.target.classList.contains('beat-select')) {
                refreshOptions();
            }
        });

        // Remove row
        beatContainer.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-beat')) {
                e.target.closest('.beat-row').remove();
                refreshOptions();
            }
        });
    </script>
@endsection
