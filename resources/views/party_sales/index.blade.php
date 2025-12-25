@extends('layouts.master')
@include('modals.denomination_modal')
<style>
.icon-btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    padding: 5px 10px;
}

.icon-btn .btn-text {
    display: none; /* hide text by default */
    margin-left: 5px;
}

.icon-btn:hover .btn-text {
    display: inline; /* show text on hover */
}
    
</style>
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
            <button type="submit" class="btn btn-primary me-2">Filter</button>
            <a href="{{ route('party-sales.index') }}" class="btn btn-secondary">Reset</a>
        </form>

        <a href="{{ route('party-sales.create') }}" class="btn btn-primary mb-3">Add New</a>
        @if($sales->isNotEmpty())
            <a href="{{ route('party-sales.download', request()->all()) }}" class="btn btn-success mb-3">Download Excel</a>
        @endif

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @php
            $sort = request('sort') === 'asc' ? 'desc' : 'asc';
            $currentSalesman = null;
            $serial = 1;
        @endphp
        <form method="POST" action="{{ route('bulk-update') }}">
            @csrf
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>
                            <a href="{{ route('party-sales.index', array_merge(request()->all(), ['sort' => $sort])) }}">
                                Customer Name
                                @if(request('sort') === 'asc') &#9650; @elseif(request('sort') === 'desc') &#9660; @endif
                            </a>
                        </th>
                        <th>Bill No</th>
                        <th>Bill Date</th>
                        <th>Aging(days)</th>
                        <th>Amount</th>
                        <th>CD</th>
                        <th>Product Return</th>
                        <th>Online Payment</th>
                        <th>Amount Received</th>
                        <th>Balance</th>
                        <th>Beat</th>
                        <th>Remarks</th>
                        <th>Action</th>
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
                            <tr style="font-weight:bold; text-align:center;">
                                <td colspan="13" style=" background-color:#c0d3ef;">{{ $sale->beat->salesman }}</td>
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
                            <td>{{ $sale->customer_name }}
                                @if($sale->modified)
                                    <span class="badge bg-success ms-2">Modified</span>
                                @endif
                            </td>
                            <td>{{ $sale->bill_no }}</td>
                            <td>{{ $sale->bill_date ? \Carbon\Carbon::parse($sale->bill_date)->format('d-m-Y') : '' }}</td>
                            <td>{{ $aging }}</td>
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
                            <td>
                                <input type="number" class="form-control balance" 
                                    style="width: 100px;" 
                                    id="balance-{{ $sale->id }}" 
                                    name="sales[{{ $sale->id }}][balance]"
                                    data-amount="{{ $sale->amount }}" 
                                    readonly>
                            </td>
                            <td>{{ $sale->beat->name }}</td>
                            <td>{{ $sale->remarks }}</td>
                            <td>
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
                    <tfoot>
                        <tr style="font-weight:bold; background-color:#f0f0f0;">
                            <td colspan="7" class="text-end">Total:</td>
                            <td id="totalProductReturn">{{ $totalProductReturn }}</td>
                            <td id="totalOnlinePayment">{{ $totalOnlinePayment }}</td>
                            <td id="totalAmountReceived">{{ $totalAmountReceived }}</td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
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
            const productReturn = parseFloat(document.querySelector(`input[name='sales[${saleId}][product_return]']`).value) || 0;
            const onlinePayment = parseFloat(document.querySelector(`input[name='sales[${saleId}][online_payment]']`).value) || 0;
            const amountReceived = parseFloat(document.querySelector(`input[name='sales[${saleId}][amount_received]']`).value) || 0;

            let balance = amount - (cd + productReturn + onlinePayment + amountReceived);
    
            if (balance < 0) balance = 0;

            document.getElementById(`balance-${saleId}`).value = balance;
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

            document.querySelectorAll("input[name$='[product_return]']").forEach(input => {
                totalProductReturn += parseFloat(input.value) || 0;
            });
            document.querySelectorAll("input[name$='[online_payment]']").forEach(input => {
                totalOnlinePayment += parseFloat(input.value) || 0;
            });
            document.querySelectorAll("input[name$='[amount_received]']").forEach(input => {
                totalAmountReceived += parseFloat(input.value) || 0;
            });

            document.getElementById('totalProductReturn').textContent = totalProductReturn;
            document.getElementById('totalOnlinePayment').textContent = totalOnlinePayment;
            document.getElementById('totalAmountReceived').textContent = totalAmountReceived;
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

    </script>
@endpush