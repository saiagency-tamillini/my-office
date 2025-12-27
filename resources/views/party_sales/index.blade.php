@extends('layouts.master')
@include('modals.denomination_modal')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/party_sales.css') }}">
@endpush
@section('content')
    <div class="container">
        <h2>Party Sales List</h2>

        <form method="GET" action="{{ route('party-sales.index') }}" class="mb-3">
            <div class="mb-2">
                <label class="form-label">Bill Date:</label>
                <input type="date"
                    name="bill_date"
                    class="form-control"
                    value="{{ request('bill_date', \Carbon\Carbon::today()->format('Y-m-d')) }}">
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

        <a href="{{ route('party-sales.create') }}" class="btn btn-primary mb-3">Add New</a>
        @if($sales->isNotEmpty())
            <a href="{{ route('party-sales.download', request()->all()) }}" class="btn btn-success mb-3">Download Excel</a>
            <button type="button" class="btn btn-info mb-3" onclick="printPage()">Print</button>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <div class="row mb-3">
            <div class="col-md-4">
                <input type="text" id="customerSearch" class="form-control" placeholder="Search by Customer Name...">
            </div>
        </div>

        @php
            $sort = request('sort') === 'asc' ? 'desc' : 'asc';
            $currentSalesman = null;
            $serial = 1;
        @endphp
        <form method="POST" action="{{ route('bulk-update') }}">
            @csrf
            <div id="printArea">
                @if($selectedBeat)
                    <div class="text-center mb-3 print-beat-heading">
                        <h4>{{ $selectedBeat->name }} ({{ request('bill_date', \Carbon\Carbon::today()->format('d-m-Y')) }})</h4>
                        {{-- <p>Date: {{ request('bill_date', \Carbon\Carbon::today()->format('d-m-Y')) }}</p> --}}
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
                    <tbody>
                        @php
                            $totalProductReturn = 0;
                            $totalOnlinePayment = 0;
                            $totalAmountReceived = 0;
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
                                $totalProductReturn += $sale->product_return ?? 0;
                                $totalOnlinePayment += $sale->online_payment ?? 0;
                                $totalAmountReceived += $sale->amount_received ?? 0;
                            @endphp
                            <tr>
                                <td>{{ $serial++ }}</td>
                                <td class="customer-name">
                                    <select name="sales[{{ $sale->id }}][customer_id]" class="form-control w-100">
                                        {{-- <option value="">Select Customer</option> --}}
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
                                <td class="date-col">{{ $sale->bill_date ? \Carbon\Carbon::parse($sale->bill_date)->format('d-m-Y') : '' }}</td>
                                <td class="aging-col">{{ $aging }}</td>
                                <td>{{ $sale->amount }}</td>
                                <td>
                                    <input type="number" class="form-control"
                                        name="sales[{{ $sale->id }}][cd]"
                                        value="{{ $sale->cd }}"
                                        max="{{ $sale->amount }}"
                                        oninput="validateMax(this, {{ $sale->amount }}); updateBalance({{ $sale->id }}, {{ $sale->amount }})">
                                </td>
                                <td>
                                    <input type="number" class="form-control"
                                        name="sales[{{ $sale->id }}][product_return]"
                                        value="{{ $sale->product_return }}"
                                        max="{{ $sale->amount }}"
                                        oninput="validateMax(this, {{ $sale->amount }}); updateBalance({{ $sale->id }}, {{ $sale->amount }})">
                                </td>
                                <td>
                                    <input type="number" class="form-control"
                                        name="sales[{{ $sale->id }}][online_payment]"
                                        value="{{ $sale->online_payment }}"
                                        oninput="updateBalance({{ $sale->id }}, {{ $sale->amount }})">
                                </td>
                                <td>
                                    <input type="number" class="form-control"
                                        name="sales[{{ $sale->id }}][amount_received]"
                                        value="{{ $sale->amount_received }}"
                                        oninput="updateBalance({{ $sale->id }}, {{ $sale->amount }})">
                                </td>
                                <td class="hide-print">
                                    <input type="number" class="form-control balance" 
                                        style="width: 100px;" 
                                        id="balance-{{ $sale->id }}" 
                                        name="sales[{{ $sale->id }}][balance]"
                                        data-amount="{{ $sale->amount }}" 
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
                                <td colspan="7" class="text-end">Total:</td>
                                <td id="totalProductReturn">{{ $totalProductReturn }}</td>
                                <td id="totalOnlinePayment">{{ $totalOnlinePayment }}</td>
                                <td id="totalAmountReceived">{{ $totalAmountReceived }}</td>
                                <td id="totalBalance"></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
            @if($sales->isNotEmpty())
                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-success">
                        Save Changes
                    </button>
                </div>
            @endif
        </form>
    </div>
@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.balance').forEach(balanceInput => {
                const saleId = balanceInput.id.split('-')[1];                
                const amount = parseFloat(balanceInput.dataset.amount);                
                updateBalance(saleId, amount);
            });
        });
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
        function updateBalance(saleId, amount) {
            const cd = parseFloat(document.querySelector(`input[name='sales[${saleId}][cd]']`).value) || 0;
            const productReturnInput = document.querySelector(`input[name='sales[${saleId}][product_return]']`);
            const onlinePaymentInput = document.querySelector(`input[name='sales[${saleId}][online_payment]']`);
            const amountReceivedInput = document.querySelector(`input[name='sales[${saleId}][amount_received]']`);

            const productReturn = parseFloat(productReturnInput.value) || 0;
            const onlinePayment = parseFloat(onlinePaymentInput.value) || 0;
            const amountReceived = parseFloat(amountReceivedInput.value) || 0;

            let balance = amount - (cd + productReturn + onlinePayment + amountReceived);
            const balanceInput = document.getElementById(`balance-${saleId}`);
            if (balance < 0) {
                balanceInput.style.border = "2px solid green";
                balanceInput.style.color = "green";
            } else {
                balanceInput.style.border = "";
                balanceInput.style.color = "black";
            }
            balanceInput.value = balance;
            updateTotals();
        }

        function updateTotals() {
            let totalProductReturn = 0;
            let totalOnlinePayment = 0;
            let totalAmountReceived = 0;
            let totalBalance = 0;

            document.querySelectorAll("input[name$='[product_return]']").forEach(input => {
                totalProductReturn += parseFloat(input.value) || 0;
            });
            document.querySelectorAll("input[name$='[online_payment]']").forEach(input => {
                totalOnlinePayment += parseFloat(input.value) || 0;
            });
            document.querySelectorAll("input[name$='[amount_received]']").forEach(input => {
                totalAmountReceived += parseFloat(input.value) || 0;
            });
            document.querySelectorAll("input.balance").forEach(input => {
                totalBalance += parseFloat(input.value) || 0;
            });

            document.getElementById('totalProductReturn').textContent = totalProductReturn;
            document.getElementById('totalOnlinePayment').textContent = totalOnlinePayment;
            document.getElementById('totalAmountReceived').textContent = totalAmountReceived;
            const totalBalanceCell = document.getElementById('totalBalance');
            if(totalBalanceCell) totalBalanceCell.textContent = totalBalance;
        }

        function printPage() {
            // const printContents = document.getElementById('printArea').innerHTML;
            // const originalContents = document.body.innerHTML;

            // document.body.innerHTML = printContents;
            // window.print();
            // document.body.innerHTML = originalContents;
            // location.reload();
            window.print();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form[action="{{ route('bulk-update') }}"]');
            const PrevTotalAmount = {{ $totalAmountReceived}};

            form.addEventListener('submit', function(e) {
                e.preventDefault(); 
                var denominationModal = new bootstrap.Modal(document.getElementById('denominationModal'));
                denominationModal.show();
            });

            
            document.getElementById('submitWithDenomination').addEventListener('click', function() {
                const totalAmountReceived = parseInt(document.getElementById('totalAmountReceived').textContent) || 0;
                const totalDen = parseInt(document.getElementById('denominationTotal').value) || 0;
                if ((PrevTotalAmount + totalDen) !== totalAmountReceived) {
                    document.getElementById('denominationError').classList.remove('d-none');
                } else {
                    document.getElementById('denominationError').classList.add('d-none');
                    form.submit();
                }
            });
        });


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
    </script>
@endpush