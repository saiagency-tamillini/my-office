<table class="table table-bordered">
    <thead>
        <tr>
            <th>
                <input type="checkbox" id="selectAllCredits">
            </th>
            <th>Customer</th>
            <th>Bill No</th>
            <th>Bill Date</th>
            <th>Balance</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sales as $sale)
        <tr>
            <td>
                <input type="checkbox"
                       class="credit-checkbox"
                       value="{{ $sale->id }}"
                       data-customer="{{ $sale->customer->name }}"
                       data-bill="{{ $sale->bill_no }}"
                       data-balance="{{ $sale->latest_balance }}"
                       data-date="{{ $sale->bill_date }}">
            </td>
            <td>{{ $sale->customer->name }}</td>
            <td>{{ $sale->bill_no }}</td>
            <td>{{ \Carbon\Carbon::parse($sale->bill_date)->format('d-m-Y') }}</td>
            {{-- <td>{{ $sale->amount }}</td> --}}
            <td>{{ $sale->latest_balance }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
