<!DOCTYPE html>
<html>
<head>
    <title>Salesman Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table, th, td {
            border: none;
        }

        th, td {
            padding: 8px;
            font-size: 12px;
        }

        .header {
            margin-bottom: 20px;
        }

        .paid {
            background-color: #d4edda;
        }

        .pending {
            background-color: #fff3cd;
        }
    </style>
</head>
<body>

    <h2>Customer Payment Report</h2>
    <p><strong>Customer:</strong> {{ $customer->name }}</p>

    @foreach($paymentEntries as $billNo => $entries)

        @php
            $isPaid = optional($entries->first())->is_paid;
        @endphp

        <h4>
            Bill No: {{ $billNo }}
            ({{ $isPaid ? 'Paid' : 'Pending' }})
        </h4>

        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Online Payment</th>
                    <th>Amount Received</th>
                    <th>Balance</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entries as $entry)
                    <tr>
                        <td>{{ $entry->customer->name }}</td>
                        <td>{{ $entry->payment_date->format('d M Y') }}</td>
                        <td>{{ $entry->amount }}</td>
                        <td>{{ $entry->online_payment ?? '-' }}</td>
                        <td>{{ $entry->amount_received ?? '-' }}</td>
                        <td>{{ $entry->balance }}</td>
                        <td>{{ $entry->remarks }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    @endforeach

</body>
</html>