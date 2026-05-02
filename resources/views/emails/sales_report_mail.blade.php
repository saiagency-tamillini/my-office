<h2>Sales Report</h2>

<table border="1" cellpadding="8" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>Customer</th>
            <th>Bill No</th>
            <th>Bill Date</th>
            <th>Amount</th>
            <th>Balance</th>
            <th>Beat</th>
            <th>Salesman</th>
        </tr>
    </thead>

    <tbody>
        @foreach($sales as $sale)
            <tr>
                <td>{{ $sale->customer->name ?? '' }}</td>
                <td>{{ $sale->bill_no }}</td>
                <td>{{ $sale->bill_date }}</td>
                <td>{{ $sale->amount }}</td>
                <td>{{ $sale->latest_balance }}</td>
                <td>{{ $sale->beat->name ?? '' }}</td>
                <td>{{ $sale->beat->salesman ?? '' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>