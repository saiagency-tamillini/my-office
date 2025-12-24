@extends('layouts.master')

@section('content')
<div class="container">
    <h2>Edit Party Sale</h2>

    <a href="{{ route('party-sales.index') }}" class="btn btn-secondary mb-3">Back to List</a>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('party-sales.update', $partySale->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="beat_id" class="form-label">Beat / Salesman</label>
            <select name="beat_id" id="beat_id" class="form-control" required>
                <option value="">Select Beat</option>
                @foreach($beats as $beat)
                    <option value="{{ $beat->id }}" {{ $partySale->beat_id == $beat->id ? 'selected' : '' }}>
                        {{ $beat->salesman.'-('. $beat->name.')' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="customer_name" class="form-label">Customer Name</label>
            <input type="text" name="customer_name" id="customer_name" class="form-control" value="{{ $partySale->customer_name }}" required>
        </div>

        <div class="mb-3">
            <label for="bill_no" class="form-label">Bill No</label>
            <input type="text" name="bill_no" id="bill_no" class="form-control" value="{{ $partySale->bill_no }}">
        </div>

        <div class="mb-3">
            <label for="bill_date" class="form-label">Bill Date</label>
            <input type="date" name="bill_date" id="bill_date" class="form-control" value="{{ $partySale->bill_date ? $partySale->bill_date->format('Y-m-d') : '' }}">
        </div>

        <div class="mb-3">
            <label for="aging" class="form-label">Aging</label>
            <input type="text" name="aging" id="aging" class="form-control" value="{{ $partySale->aging }}">
        </div>

        <div class="mb-3">
            <label for="amount" class="form-label">Amount</label>
            <input type="number" step="0.01" name="amount" id="amount" class="form-control" value="{{ $partySale->amount }}">
        </div>

        <div class="mb-3">
            <label for="cd" class="form-label">CD</label>
            <input type="text" name="cd" id="cd" class="form-control" value="{{ $partySale->cd }}">
        </div>

        <div class="mb-3">
            <label for="product_return" class="form-label">Product Return</label>
            <input type="text" name="product_return" id="product_return" class="form-control" value="{{ $partySale->product_return }}">
        </div>

        <div class="mb-3">
            <label for="online_payment" class="form-label">Online Payment</label>
            <input type="text" name="online_payment" id="online_payment" class="form-control" value="{{ $partySale->online_payment }}">
        </div>

        <div class="mb-3">
            <label for="amount_received" class="form-label">Amount Received</label>
            <input type="text" name="amount_received" id="amount_received" class="form-control" value="{{ $partySale->amount_received }}">
        </div>

        <div class="mb-3">
            <label for="balance" class="form-label">Balance</label>
            <input type="text" name="balance" id="balance" class="form-control" value="{{ $partySale->balance }}">
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
    </form>
</div>
@endsection
