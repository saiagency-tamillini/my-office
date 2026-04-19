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
                <form id="filterForm"
                    method="GET"
                    action="{{ route('collections.index') }}"
                    class="row g-3 align-items-end">

                    {{-- Salesman --}}
                    <div class="col-12 col-md-2">
                        <label class="form-label fw-semibold">Salesman</label>
                        <select name="salesman" class="form-select">
                            <option value="">Select Salesman</option>
                            @foreach($salesmen as $salesman)
                                <option value="{{ $salesman }}"
                                    {{ request('salesman') == $salesman ? 'selected' : '' }}>
                                    {{ $salesman }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Beat --}}
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold">Beat</label>
                        <div class="d-flex gap-2">
                            @php
                                $selectedBeats = array_filter((array) request('beat_ids'));
                            @endphp

                            <div id="beat-container" class="flex-grow-1">
                                @forelse($selectedBeats as $selectedBeat)
                                    <div class="d-flex align-items-center gap-2 beat-row mt-2">
                                        <select name="beat_ids[]" class="form-select beat-select">
                                            <option value="">-- Select Beat --</option>
                                            @foreach($beats as $beat)
                                                <option value="{{ $beat->id }}"
                                                    {{ $beat->id == $selectedBeat ? 'selected' : '' }}>
                                                    {{ $beat->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="button"
                                                class="btn btn-outline-danger btn-sm remove-beat">
                                            ❌
                                        </button>
                                    </div>
                                @empty
                                    <div class="d-flex align-items-center gap-2 beat-row">
                                        <select name="beat_ids[]" class="form-select beat-select">
                                            <option value="">-- Select Beat --</option>
                                            @foreach($beats as $beat)
                                                <option value="{{ $beat->id }}">{{ $beat->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button"
                                                class="btn btn-outline-danger btn-sm remove-beat d-none">
                                            ❌
                                        </button>
                                    </div>
                                @endforelse
                            </div>

                            <button type="button"
                                    class="btn btn-sm btn-outline-primary h-fit"
                                    id="add-beat">
                                + Add
                            </button>
                        </div>
                    </div>

                    {{-- Payment Date + Checkbox --}}
                    <div class="col-12 col-md-2">
                        <div class="form-check mt-2">
                            <input class="form-check-input"
                                type="checkbox"
                                name="include_party_sale_collection"
                                value="1"
                                id="include_party_sale_collection"
                                {{ request('include_party_sale_collection') ? 'checked' : '' }}>
                            <label class="form-check-label small" for="include_party_sale_collection">
                                Include Party Sale
                            </label>
                        </div>
                        <label class="form-label fw-semibold">Payment Date</label>
                        <input type="date"
                            name="date"
                            class="form-control"
                            value="{{ request('date') }}">

                        {{-- <div class="form-check mt-2">
                            <input class="form-check-input"
                                type="checkbox"
                                name="include_party_sale_collection"
                                value="1"
                                id="include_party_sale_collection"
                                {{ request('include_party_sale_collection') ? 'checked' : '' }}>
                            <label class="form-check-label small" for="include_party_sale_collection">
                                Include Party Sale
                            </label>
                        </div> --}}
                    </div>

                    {{-- Bill Date --}}
                    <div class="col-12 col-md-2">
                        <label class="form-label fw-semibold">Bill Date</label>
                        <input type="date"
                            name="bill_date"
                            class="form-control"
                            value="{{ request('bill_date') }}">
                    </div>

                    {{-- Per Page --}}
                    <div class="col-12 col-md-1">
                        <label class="form-label fw-semibold">Show</label>
                        <select name="per_page" class="form-select" id="perPage">
                            @foreach([10,25,50,100,200,500] as $n)
                                <option value="{{ $n }}"
                                    {{ request('per_page', 25) == $n ? 'selected' : '' }}>
                                    {{ $n }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Actions --}}
                    <div class="col-12 col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            Apply
                        </button>
                        <a href="{{ route('collections.index') }}"
                        class="btn btn-outline-secondary w-100">
                            Reset
                        </a>
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
                            <button type="button" class="btn btn-secondary" id="calcBalanceBtnCollections" data-balance-calc-toggle aria-pressed="false">
                                Calculate Total
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
                                <table class="table table-hover table-striped align-middle mb-0" data-balance-calc="collections">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="hide-print">Remove</th>
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
                                                <td class="hide-print">
                                                    <button type="button" class="btn btn-sm btn-danger remove-row">
                                                        Remove
                                                    </button>
                                                </td>
                                                <td class="text-muted">{{ $entries->firstItem() + $i }}</td>
                                                <td class="fw-semibold">{{ optional($entry->payment_date)->format('d M Y') ?? '-' }}</td>
                                                <td>{{ $salesmanName ?? '-' }}</td>
                                                <td class="text-truncate" style="max-width: 220px;">{{ $customerName }}</td>
                                                <td>{{ $entry->bill_no ?? '-' }}</td>

                                                <td class="text-end amount">{{ number_format((float)($entry->amount ?? 0), 2) }}</td>
                                                <td class="text-end cd">{{ number_format((float)($entry->cd ?? 0), 2) }}</td>
                                                <td class="text-end return">{{ number_format((float)($entry->product_return ?? 0), 2) }}</td>
                                                <td class="text-end online">{{ number_format((float)($entry->online_payment ?? 0), 2) }}</td>
                                                <td class="text-end fw-semibold received">{{ number_format((float)($entry->amount_received ?? 0), 2) }}</td>
                                                <td class="text-end fw-semibold balance">{{ number_format((float)($entry->balance ?? 0), 2) }}</td>

                                                <td class="text-truncate hide-print" style="max-width: 240px;">{{ $entry->remarks ?? '-' }}</td>

                                                <td class="hide-print">
                                                    <span class="badge bg-{{ $badge }}">{{ $statusText }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>

                                    <tfoot class="table-light">
                                        <tr>
                                            <th class="hide-print"></th>
                                            <th colspan="5" class="text-end">Totals:</th>
                                            <th class="text-end" id="totalAmount">{{ number_format($totalAmount, 2) }}</th>
                                            <th colspan="3"></th>
                                            <th class="text-end" id="totalReceived">{{ number_format($totalReceived, 2) }}</th>
                                            <th class="text-end" id="totalBalance">{{ number_format($totalBalance, 2) }}</th>
                                            <th class="hide-print"colspan="2"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                                <div id="denominationSection" class="card shadow-sm border-0 mt-4" style="max-width: 300px; margin: 0 auto;">
                                    <div class="card-header bg-white fw-semibold">
                                        Denomination
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-bordered mb-0" id="denominationTable">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Denomination</th>
                                                        <th width="150">No's</th>
                                                        <th width="150" class="text-end">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php
                                                        $denominations = [500, 200, 100, 50, 20, 10];
                                                    @endphp

                                                    @foreach($denominations as $note)
                                                        <tr>
                                                            <td>₹ {{ $note }}</td>
                                                            <td>
                                                                <input type="number"
                                                                    min="0"
                                                                    class="form-control denom-input"
                                                                    data-value="{{ $note }}"
                                                                    placeholder="0">
                                                            </td>
                                                            <td class="text-end denom-total">0.00</td>
                                                        </tr>
                                                    @endforeach

                                                    {{-- Coins --}}
                                                    <tr>
                                                        <td>Coins</td>
                                                        <td>
                                                            <input type="number"
                                                                min="0"
                                                                class="form-control denom-input"
                                                                data-value="1"
                                                                placeholder="0">
                                                        </td>
                                                        <td class="text-end denom-total">0.00</td>
                                                    </tr>
                                                </tbody>

                                                <tfoot class="table-light">
                                                    <tr>
                                                        <th colspan="2" class="text-end">Overall Total:</th>
                                                        <th class="text-end fw-bold" id="overallDenomTotal">0.00</th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
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
        window.__balanceCalc_collections = {
            toggleButton: '#calcBalanceBtnCollections',
            skipRowSelector: null,
            columns: [
                { key: 'amount', label: 'Amount', rowSelector: 'td.amount' },
                { key: 'cd', label: 'CD', rowSelector: 'td.cd' },
                { key: 'product_return', label: 'Product Return', rowSelector: 'td.return' },
                { key: 'online_payment', label: 'Online Payment', rowSelector: 'td.online' },
                { key: 'amount_received', label: 'Amount Received', rowSelector: 'td.received' },
                { key: 'balance', label: 'Balance', rowSelector: 'td.balance' },
            ]
        };
    </script>
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
        function recalculateTotals() {
            let totalAmount = 0;
            let totalReceived = 0;
            let totalBalance = 0;

            document.querySelectorAll('#printArea tbody tr').forEach(row => {
                const amount = parseFloat(row.querySelector('.amount')?.innerText.replace(/,/g, '') || 0);
                const received = parseFloat(row.querySelector('.received')?.innerText.replace(/,/g, '') || 0);
                const balance = parseFloat(row.querySelector('.balance')?.innerText.replace(/,/g, '') || 0);

                totalAmount += amount;
                totalReceived += received;
                totalBalance += balance;
            });

            document.getElementById('totalAmount').innerText = totalAmount.toFixed(2);
            document.getElementById('totalReceived').innerText = totalReceived.toFixed(2);
            document.getElementById('totalBalance').innerText = totalBalance.toFixed(2);
        }

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-row')) {
                const row = e.target.closest('tr');
                if (row) {
                    row.remove();
                    recalculateTotals(); 
                }
            }
        });

        function calculateDenomination() {
            let overallTotal = 0;

            document.querySelectorAll('#denominationTable tbody tr').forEach(row => {
                const input = row.querySelector('.denom-input');
                const totalCell = row.querySelector('.denom-total');

                if (!input) return;

                const value = parseFloat(input.dataset.value || 0);
                const count = parseFloat(input.value || 0);

                const rowTotal = value * count;
                totalCell.innerText = rowTotal.toFixed(2);

                overallTotal += rowTotal;
            });

            document.getElementById('overallDenomTotal').innerText = overallTotal.toFixed(2);
        }

        document.addEventListener('input', function (e) {
            if (e.target.classList.contains('denom-input')) {
                calculateDenomination();
            }
        });


    </script>
@endsection
