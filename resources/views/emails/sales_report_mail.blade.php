<h2>Sales Report</h2>
@php
    $totalBalance = 0;
@endphp
<table border="1" cellpadding="8" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>Customer</th>
            <th>Bill No</th>
            <th>Bill Date</th>
            <th>Amount</th>
            <th>Aging</th>
            <th>Balance</th>
            <th>Beat</th>
        </tr>
    </thead>

    <tbody>
        @foreach($sales as $sale)
            @php
                $billDate = \Carbon\Carbon::parse(date('Y-m-d', strtotime($sale->bill_date)));
                $aging = $billDate->diffInDays(\Carbon\Carbon::today(), false);
                $totalBalance += $sale->latest_balance ?? 0;
            @endphp
            <tr>
                <td>{{ $sale->customer->name ?? '' }}</td>
                <td>{{ $sale->bill_no }}</td>
                <td>{{ $sale->bill_date ? \Carbon\Carbon::parse($sale->bill_date)->format('d-m-Y') : '' }}</td>
                <td>{{ $sale->amount }}</td>
                <td>{{ $aging }}</td>
                <td>{{ $sale->latest_balance }}</td>
                <td>{{ $sale->beat->name ?? '' }}</td>
            </tr>
        @endforeach
    </tbody>
     <tfoot >
        <tr style="font-weight:bold; background-color:#f0f0f0;">
            <td colspan="5" class="text-end fw-bold">Total:</td>
            <td id="totalBalance" class="fw-bold">{{ $totalBalance }}</td>
            <td colspan="1"></td>
        </tr>
    </tfoot>
</table>