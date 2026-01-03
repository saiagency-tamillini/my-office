@extends('layouts.master')
@include('modals.credit_modal')
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
            <div class="mb-2">Filter by Salesman:</div>
            <div class="d-flex flex-wrap mb-2">
                @foreach($salesmen as $salesman)
                    <div class="form-check me-3">
                        <input class="form-check-input" type="checkbox" name="salesmen[]" 
                            value="{{ $salesman }}" id="salesman_{{ $loop->index }}"
                            {{ is_array(request('salesmen')) && in_array($salesman, request('salesmen')) ? 'checked' : '' }}>
                        <label class="form-check-label" for="salesman_{{ $loop->index }}">
                            {{ $salesman }}
                        </label>
                    </div>
                @endforeach
            </div>
            <div class="mb-2">
                <label class="form-label">Filter by Beat:</label>
                <select name="beat_id" class="form-select">
                    <option value="">-- All Beats --</option>
                    @foreach($beats as $beat)
                        <option value="{{ $beat->id }}"
                            {{ request('beat_id') == $beat->id ? 'selected' : '' }}>
                            {{ $beat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary me-2">Filter</button>
            <a href="{{ route('party-sales.index') }}" class="btn btn-secondary">Reset</a>
        </form>
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
            @if($selectedBeat)
                <div class="text-center mb-3 print-beat-heading">
                    <h4>{{ $selectedBeat->name }} ({{ request('bill_date', \Carbon\Carbon::today()->format('d-m-Y')) }})</h4>
                </div>
            @endif
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th style="min-width: 280px;">
                            <a href="{{ route('party-sales.index', array_merge(request()->all(), ['sort' => $sort])) }}">
                                Customer Name
                                @if(request('sort') === 'asc') &#9650; @elseif(request('sort') === 'desc') &#9660; @endif
                            </a>
                        </th>
                        <th>Bill No</th>
                        <th>Bill Date</th>
                        <th>Aging<br>(days)</th>
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
                        // $totalProductReturn = 0;
                        // $totalOnlinePayment = 0;
                        // $totalAmountReceived = 0;
                        $totalBalance = 0;
                    @endphp
                    @forelse($sales as $sale)
                        @if($currentSalesman !== $sale->beat->salesman)
                            <tr class="salesman-row">
                                <td colspan="14" class="salesman-cell">{{ $sale->beat->salesman }}</td>
                            </tr>
                            @php
                                $currentSalesman = $sale->beat->salesman;
                                $serial = 1;
                            @endphp
                        @endif
                        @php
                            $billDate = \Carbon\Carbon::parse(date('Y-m-d', strtotime($sale->bill_date)));
                            $aging = $billDate->diffInDays(\Carbon\Carbon::today(), false);
                            // $totalProductReturn += $sale->product_return ?? 0;
                            // $totalOnlinePayment += $sale->online_payment ?? 0;
                            // $totalAmountReceived += $sale->amount_received ?? 0;
                            $totalBalance += $sale->balance ?? 0;
                        @endphp
                        <tr>
                            <td>{{ $serial++ }}</td>
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
                            <td>{{ $sale->bill_no }}</td>
                            <td class="date-col">{{ $sale->bill_date ? \Carbon\Carbon::parse($sale->bill_date)->format('d-m-Y') : '' }}
                                <input type="hidden"
                                    name="sales[{{ $sale->id }}][bill_date]"
                                    value="{{ $sale->bill_date }}">
                            </td>
                            <td class="aging-col">{{ $aging }}</td>
                            <td>{{ $sale->balance  }}</td>
                            <td>{{  $sale->cd }}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="hide-print">
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
        // document.getElementById('addSelectedCredits').addEventListener('click', function () {
        //     console.log('entry');

        //     let selected = [];

        //     document.querySelectorAll('.credit-checkbox:checked').forEach(cb => {
        //         selected.push({
        //             id: cb.value,
        //             customer: cb.dataset.customer,
        //             bill: cb.dataset.bill,
        //             balance: cb.dataset.balance
        //         });
        //     });

        //     if (selected.length === 0) {
        //         alert('Please select at least one record');
        //         return;
        //     }

        //     console.log(selected); 

        //     bootstrap.Modal.getInstance(
        //         document.getElementById('creditModal')
        //     ).hide();
        // });
    </script>

    <script>
        document.getElementById('addSelectedCredits').addEventListener('click', function () {

            let selected = [];

            document.querySelectorAll('.credit-checkbox:checked').forEach(cb => {
                selected.push({
                    id: cb.value,
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
                const formattedDate = formatDate(item.date);
                const agingDays = calculateAging(item.date);
                tr.innerHTML = `
                    <td>*</td>
                    <td class="customer-name">${item.customer}</td>
                    <td>${item.bill}</td>
                    <td>${formattedDate}</td>
                    <td class="aging-col">${agingDays}</td>
                    <td>${item.balance}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="hide-print">${item.balance}</td>
                    <td class="hide-print">-</td>
                    <td class="hide-print">Credit Entry</td>
                    <td class="hide-print">
                        <button class="btn btn-sm btn-danger remove-credit">
                            Remove
                        </button>
                    </td>
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

                // Skip salesman / credit headers
                if (row.classList.contains('salesman-row')) return;

                // Normal rows have balance input or balance cell
                const balanceInput = row.querySelector('input.balance');
                const balanceCell  = row.querySelector('td.hide-print');

                if (balanceInput) {
                    total += parseFloat(balanceInput.value || 0);
                } else if (balanceCell) {
                    total += parseFloat(balanceCell.textContent || 0);
                }
            });

            document.getElementById('totalBalance').textContent = total.toFixed(2);
        }
    </script>



@endpush