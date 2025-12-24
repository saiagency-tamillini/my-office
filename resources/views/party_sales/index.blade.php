@extends('layouts.master')

@section('content')
    <div class="container">
        <h2>Party Sales List</h2>

        <form method="GET" action="{{ route('party-sales.index') }}" class="mb-3">
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
        <a href="{{ route('party-sales.download') }}" class="btn btn-success mb-3">Download Excel</a>

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
                    @foreach($sales as $sale)
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
                        @endphp
                        <tr>
                            <td>{{ $serial++ }}</td>
                            <td>{{ $sale->customer_name }}</td>
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
                                <a href="{{ route('party-sales.edit', $sale->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                 <button type="button" class="btn btn-sm btn-danger"
                                        onclick="deleteSale({{ $sale->id }})">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="text-end mt-3">
                <button type="submit" class="btn btn-success">
                    Save Changes
                </button>
            </div>
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
    </script>
@endpush