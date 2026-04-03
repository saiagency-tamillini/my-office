@extends('layouts.master')
@include('modals.credit_modal')
@include('modals.trip_modal')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/party_sales.css') }}">
@endpush
@section('content')
    <div class="container">
        <div class="d-flex justify-content-between">
            <h2>Trip Sheet</h2>
            <button type="button" class="btn btn-primary mb-3" id="openCreditModal">
                Credit Details
            </button>
        </div>

        <div class="mb-3">

            <!-- Filter Header -->
            <button class="btn btn-outline-primary w-100 text-start d-flex justify-content-between align-items-center"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#filterPanel"
                    aria-expanded="false"
                    aria-controls="filterPanel">
                Filters
                <i class="bi bi-chevron-down" id="filterIcon"></i>
            </button>

            <!-- Collapsible Filters -->
            <div class="collapse mt-2" id="filterPanel">
                <form method="GET" action="{{ route('trip.report') }}">

                    <div class="d-flex flex-column p-3 border rounded bg-light">

                        <div class="d-flex gap-5 flex-wrap">

                            <!-- Single Bill Date -->
                            <div class="mb-2 d-flex gap-2 align-items-start flex-wrap">
                                <label class="form-label fw-bold mb-0">Bill Date:</label>

                                <input type="date"
                                    name="bill_date"
                                    class="form-control w-auto"
                                    value="{{ request('bill_date', \Carbon\Carbon::today()->format('Y-m-d')) }}"
                                    onclick="this.showPicker()"
                                    onfocus="this.showPicker()">
                            </div>

                            <!-- Salesman Filter -->
                            <div class="d-flex flex-column mb-2">
                                <div class="fw-bold">Filter by Salesman:</div>

                                @foreach($salesmen as $salesman)
                                    <div class="form-check">
                                        <input class="form-check-input border border-dark"
                                            type="checkbox"
                                            name="salesmen[]"
                                            value="{{ $salesman }}"
                                            id="salesman_{{ $loop->index }}"
                                            {{ is_array(request('salesmen')) && in_array($salesman, request('salesmen')) ? 'checked' : '' }}>

                                        <label class="form-check-label"
                                            for="salesman_{{ $loop->index }}">
                                            {{ $salesman }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Beat Filter -->
                            <div>
                                <label class="form-label fw-bold">Filter by Beat:</label>

                                <div class="d-flex gap-2">

                                    @php
                                        $filteredBeats = array_filter((array) request('beat_ids'));
                                    @endphp

                                    <div id="beat-container">

                                        @forelse($filteredBeats as $filteredBeat)
                                            <div class="d-flex align-items-center gap-2 beat-row mb-2">
                                                <select name="beat_ids[]" class="form-select beat-select">
                                                    <option value="">-- Select Beat --</option>
                                                    @foreach($beats as $beat)
                                                        <option value="{{ $beat->id }}"
                                                            {{ $beat->id == $filteredBeat ? 'selected' : '' }}>
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
                                                        <option value="{{ $beat->id }}">
                                                            {{ $beat->name }}
                                                        </option>
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
                                        + Add More
                                    </button>

                                </div>
                            </div>

                        </div>

                        <!-- Filter Buttons -->
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary me-2">Filter</button>
                            <a href="{{ route('trip.report') }}" class="btn btn-secondary">Reset</a>
                        </div>

                    </div>
                </form>
            </div>
        </div>



        @if($sales->isNotEmpty())
            <div class="mt-2">
                <a href="{{ route('party-sales.download', request()->all()) }}" class="btn btn-success mb-3">Download Excel</a>
                <button type="button" class="btn btn-info mb-3" onclick="printPage()">Print</button>
            </div>
        @endif

        <div class="row mb-3">
            <div class="col-md-4">
                <input type="text" id="customerSearch" class="form-control" placeholder="Search by Customer Name...">
            </div>
        </div>
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @php
            $sort = request('sort') === 'asc' ? 'desc' : 'asc';
            $currentSalesman = null;
            $serial = 1;
        @endphp
        <div id="printArea">
            @if($selectedBeats->isNotEmpty())
                <div class="text-center mb-3 print-beat-heading">
                    <h4>
                        Beats:
                        {{ $selectedBeats->pluck('name')->implode(', ') }}
                    </h4>
                </div>
            @endif
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th class="hide-print"></th>
                        <th style="min-width: 280px;">
                            <a href="{{ route('trip.report', array_merge(request()->all(), ['sort' => $sort])) }}">
                                Customer Name
                                @if(request('sort') === 'asc') &#9650; @elseif(request('sort') === 'desc') &#9660; @endif
                            </a>
                        </th>
                        <th>Bill No</th>
                        <th class="hide-print">Bill Date</th>
                        <th class="hide-print">Aging<br>(days)</th>
                        <th>Amount</th>
                        <th>CD</th>
                        <th>Product Return</th>
                        <th>Online Payment</th>
                        <th>Amount Received</th>
                        <th class="hide-print">Balance</th>
                        <th class="hide-print">Beat</th>
                        <th class="hide-print">Remarks</th>
                        <th class="hide-print">Action</th>
                    </tr>
                </thead>
                <tbody id="tripSheetBody">
                    @php
                        $totalBalance = 0;
                    @endphp
                    @forelse($sales as $sale)
                        @if($currentSalesman !== $sale->beat->salesman)
                            <tr class="salesman-row">
                                <td colspan="14" class="salesman-cell">{{ $sale->beat->salesman }}</td>
                            </tr>
                            @php
                                $currentSalesman = $sale->beat->salesman;
                            @endphp
                        @endif
                        @php
                            $billDate = \Carbon\Carbon::parse(date('Y-m-d', strtotime($sale->bill_date)));
                            $aging = $billDate->diffInDays(\Carbon\Carbon::today(), false);
                            $totalBalance += $sale->balance ?? 0;
                        @endphp
                        <tr data-party-sale-id="{{ $sale->id }}">
                            <td class="hide-print">
                                <button type="button" class="btn btn-sm btn-danger remove-row">
                                    ❌
                                </button>
                            </td>
                            {{-- <td>{{ $serial++ }}</td> --}}
                            <td class="customer-name">
                                <select name="sales[{{ $sale->id }}][customer_id]" class="form-control w-100" disabled>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" 
                                            {{ $sale->customer_id == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }} ({{ $customer->beat->name ?? 'No Beat' }})
                                        </option>
                                    @endforeach
                                </select>
                                @if($sale->modified)
                                    <span class="badge bg-success ms-2">Modified</span>
                                @endif
                            </td>
                            <td class="bold-font">{{ $sale->bill_no }}</td>
                            <td class="date-col hide-print">{{ $sale->bill_date ? \Carbon\Carbon::parse($sale->bill_date)->format('d-m-Y') : '' }}
                                <input type="hidden"
                                    name="sales[{{ $sale->id }}][bill_date]"
                                    value="{{ $sale->bill_date }}">
                            </td>
                            <td class="aging-col hide-print">{{ $aging }}</td>
                            <td class="bold-font">{{ $sale->balance  }}</td>
                            <td class="cd-col">{{  $sale->cd }}</td>
                            <td></td>
                            <td></td>
                            <td class="total-col"></td>
                            <td class="hide-print balance-col">
                                <input type="number" class="form-control balance" 
                                    style="width: 100px;" 
                                    id="balance-{{ $sale->id }}" 
                                    name="sales[{{ $sale->id }}][balance]"
                                    data-amount="{{ $sale->balance }}"
                                    value="{{ $sale->balance }}" 
                                    readonly>
                            </td>
                            <td class="hide-print">{{ $sale->beat->name }}</td>
                            <td class="hide-print">{{ $sale->remarks }}</td>
                            <td class="hide-print">
                                <a href="{{ route('party-sales.edit', $sale->id) }}" class="btn btn-sm btn-warning icon-btn" title="Edit">
                                    <i class="fas fa-edit"></i>
                                    <span class="btn-text">Edit</span>
                                </a>
                                <button type="button" class="btn btn-sm btn-danger icon-btn" onclick="deleteSale({{ $sale->id }})" title="Delete">
                                    <i class="fas fa-trash"></i>
                                    <span class="btn-text">Delete</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="text-center text-muted">
                                No data available
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($sales->isNotEmpty())
                    <tfoot class="hide-print">
                        <tr style="font-weight:bold; background-color:#f0f0f0;">
                            <td colspan="5" class="text-end">Total:</td>
                            <td id="totalBalance">{{ $totalBalance }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
            <div class="d-flex justify-content-between">
                <div id="vehicle-entry" class="card shadow-sm mt-4" style="max-width:380px;">
                    <div class="card-header fw-bold text-center">
                        Vehicle Details
                    </div>

                    <div class="card-body p-2">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <tbody>

                                <tr>
                                    <td class="fw-semibold text-nowrap">Start KM</td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="number" min="0" class="form-control km-input" >
                                            <span class="">KM</span>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="fw-semibold text-nowrap">End KM</td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="number" min="0" class="form-control km-input">
                                            <span class="">KM</span>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="fw-semibold text-nowrap">Book No</td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm">
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>

                </div>
                <div class="mt-3 hide-print">
                    <button type="button" class="btn btn-success" id="saveTripBtn">
                        Save Trip
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        function deleteSale(id) {
            if (!confirm('Are you sure you want to delete this record?')) return;

            fetch(`/party-sales/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Delete failed');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Record deleted successfully!');
                    window.location.reload();

                } else {
                    alert('Delete failed: ' + data.message);
                }
            })
            .catch(error => {
                alert('Something went wrong!');
                console.error(error);
            });
        }
        function validateMax(input, maxValue) {
            let value = parseFloat(input.value) || 0;
            if (value > maxValue) {
                input.value = maxValue; 
                alert('Value cannot exceed ' + maxValue);
            } else if (value < 0) {
                input.value = 0; 
            }
        }
        function printPage() {
            window.print();
        }

        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('customerSearch');
            const rows = document.querySelectorAll('tbody tr');
            searchInput.addEventListener('keyup', function () {
                const searchValue = this.value.toLowerCase();
                rows.forEach(row => {
                    const customerCell = row.querySelector('.customer-name');

                    if (!customerCell) return;

                    const select  = customerCell.querySelector('select[name*="[customer_id]"]');
                    if (!select ) return;
                    
                    const customerName = select.options[select.selectedIndex].text.toLowerCase();
                    
                    if (customerName.includes(searchValue)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
            document.querySelectorAll('table input[type="number"]').forEach(input => {
                input.addEventListener('input', () => {
                    const length = input.value.length;
                    input.style.width = `${Math.max(length, 2) + 1}ch`; // min 2 chars width
                });
            });
            document.querySelectorAll('table td').forEach(td => {
                td.addEventListener('click', e => {
                    const input = td.querySelector('input, select');
                    if (input) input.focus();
                });
            });
        });
        document.getElementById('openCreditModal').addEventListener('click', function () {
            fetch('/credit-details-popup')
                .then(res => res.text())
                .then(html => {
                    document.getElementById('creditModalBody').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('creditModal')).show();
                });
        });
        document.addEventListener('change', function (e) {
            if (e.target.id === 'selectAllCredits') {
                document.querySelectorAll('.credit-checkbox')
                    .forEach(cb => cb.checked = e.target.checked);
            }
        });
    </script>

    <script>
        document.getElementById('addSelectedCredits').addEventListener('click', function () {

            let selected = [];

            document.querySelectorAll('.credit-checkbox:checked').forEach(cb => {
                selected.push({
                    id: cb.value,
                    partySaleId: cb.dataset.partySaleId,
                    paymentEntryId: cb.dataset.paymentEntryId || null,
                    customer: cb.dataset.customer,
                    bill: cb.dataset.bill,
                    balance: cb.dataset.balance,
                    date: cb.dataset.date
                });
            });

            if (selected.length === 0) {
                alert('Please select at least one record');
                return;
            }
            selected.sort((a, b) => b.customer.localeCompare(a.customer));

            const tbody = document.getElementById('tripSheetBody');

            if (!tbody.querySelector('.credit-header')) {
                const creditHeader = document.createElement('tr');
                creditHeader.className = 'salesman-row credit-header';
                creditHeader.innerHTML = `
                    <td colspan="14" class="salesman-cell text-center">
                        CREDITS
                    </td>
                `;
                tbody.appendChild(creditHeader);
            }

            selected.forEach((item, index) => {

                if (tbody.querySelector(`tr[data-credit-id="${item.id}"]`)) {
                    return;
                }

                const tr = document.createElement('tr');
                tr.setAttribute('data-credit-id', item.id);
                tr.setAttribute('data-party-sale-id', item.partySaleId || item.id);
                if (item.paymentEntryId) {
                    tr.setAttribute('data-payment-entry-id', item.paymentEntryId);
                }
                const formattedDate = formatDate(item.date);
                const agingDays = calculateAging(item.date);
                tr.innerHTML = `
                    <td class="hide-print"> 
                        <button type="button" class="btn btn-sm btn-danger remove-credit">❌</button>
                    </td>
                    <td class="customer-name">${item.customer}</td>
                    <td class="bold-font">${item.bill}</td>
                    <td class="hide-print">${formattedDate}</td>
                    <td class="aging-col hide-print">${agingDays}</td>
                    <td class="bold-font">${item.balance}</td>
                    <td class="cd-col"></td>
                    <td></td>
                    <td></td>
                    <td class="total-col"></td>
                    <td class="hide-print">${item.balance}</td>
                    <td class="hide-print">-</td>
                    <td class="hide-print">Credit Entry</td>
                `;

                tbody.querySelector('.credit-header').after(tr);
                updateTotalBalance();
            });

            bootstrap.Modal.getInstance(
                document.getElementById('creditModal')
            ).hide();
        });
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-credit')) {
                e.target.closest('tr').remove();
                updateTotalBalance(); 
            }
        });
        function formatDate(dateString) {
            const date = new Date(dateString);
            const day   = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year  = date.getFullYear();
            return `${day}-${month}-${year}`;
        }
        function calculateAging(dateString) {
            const billDate = new Date(dateString);
            const today    = new Date();

            billDate.setHours(0,0,0,0);
            today.setHours(0,0,0,0);

            const diffTime = today - billDate;
            return Math.floor(diffTime / (1000 * 60 * 60 * 24));
        }
        function updateTotalBalance() {
            let total = 0;

            document.querySelectorAll('#tripSheetBody tr').forEach(row => {

                if (row.classList.contains('salesman-row')) return;

                const balanceInput = row.querySelector('input.balance');
                // const balanceCell  = row.querySelector('td.hide-print');
                const balanceCell  = row.children[10];

                if (balanceInput) {
                    total += parseFloat(balanceInput.value || 0);
                } else if (balanceCell) {
                    total += parseFloat(balanceCell.textContent || 0);
                }
            });

            document.getElementById('totalBalance').textContent = total.toFixed(2);
        }
    </script>
    <script>
        document.getElementById('creditSearch')?.addEventListener('input', function () {
            const searchValue = this.value.toLowerCase();
            document.querySelectorAll('#creditModal table tbody tr').forEach(row => {
                const customer = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase();
                row.style.display = customer.includes(searchValue) ? '' : 'none';
            });
        });

        let sortDirection = 'asc';

        document.addEventListener('click', function (e) {

            if (e.target.closest('#customerSort')) {

                const tbody = document.querySelector('#creditModal table tbody');
                if (!tbody) return;

                const rows = Array.from(tbody.querySelectorAll('tr'));

                rows.sort((a, b) => {
                    const nameA = a.querySelector('td:nth-child(2)').textContent.trim().toLowerCase();
                    const nameB = b.querySelector('td:nth-child(2)').textContent.trim().toLowerCase();

                    return sortDirection === 'asc'
                        ? nameA.localeCompare(nameB)
                        : nameB.localeCompare(nameA);
                });

                sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';

                const icon = document.getElementById('sortIcon');
                if (icon) icon.textContent = sortDirection === 'asc' ? '⬆' : '⬇';

                rows.forEach(row => tbody.appendChild(row));
            }
        });
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
            row.className = 'd-flex align-items-center gap-2 mb-2 beat-row';

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
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-row')) {
                const row = e.target.closest('tr');
                row.remove();
                updateTotalBalance(); 
            }
        });

        const saveTripBtn = document.getElementById('saveTripBtn');
        const confirmSaveTripBtn = document.getElementById('confirmSaveTrip');
        const saveTripModalEl = document.getElementById('saveTripModal');
        const saveTripModal = saveTripModalEl ? new bootstrap.Modal(saveTripModalEl) : null;
        const saveTripForm = document.getElementById('saveTripForm');

        saveTripBtn?.addEventListener('click', function () {
            saveTripModal?.show();
        });

        function collectTripItems() {
            const rows = document.querySelectorAll('#tripSheetBody tr');
            const items = [];

            rows.forEach(row => {
                if (row.classList.contains('salesman-row')) return;

                const partySaleId = row.dataset.partySaleId || row.dataset.creditId || null;
                const paymentEntryId = row.dataset.paymentEntryId || null;

                if (!partySaleId) return;

                items.push({
                    party_sale_id: Number(partySaleId),
                    payment_entry_id: paymentEntryId ? Number(paymentEntryId) : null
                });
            });

            return items;
        }

        confirmSaveTripBtn?.addEventListener('click', async function () {
            if (!saveTripForm) return;

            const tripDateInput = saveTripForm.querySelector('input[name="trip_date"]');
            const routeInput = saveTripForm.querySelector('select[name="route_id"]');

            const tripDate = tripDateInput?.value;
            const routeId = routeInput?.value;
            const items = collectTripItems();

            if (!tripDate) {
                alert('Please select trip date');
                return;
            }

            if (!routeId) {
                alert('Please select route');
                return;
            }

            if (!items.length) {
                alert('No trip items found to save');
                return;
            }

            confirmSaveTripBtn.disabled = true;
            confirmSaveTripBtn.textContent = 'Saving...';

            try {
                const response = await fetch('{{ route('trip.save') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        trip_date: tripDate,
                        route_id: Number(routeId),
                        items
                    })
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Failed to save trip');
                }

                alert(result.message || 'Trip saved successfully');
                saveTripModal?.hide();
                window.location.reload();
            } catch (error) {
                alert(error.message || 'Something went wrong while saving trip');
            } finally {
                confirmSaveTripBtn.disabled = false;
                confirmSaveTripBtn.textContent = 'Save';
            }
        });
    </script>
@endpush