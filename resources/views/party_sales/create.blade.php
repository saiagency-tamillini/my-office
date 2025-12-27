@extends('layouts.master')

@section('content')
<div class="container">
    <h2>{{ isset($partySale) ? 'Edit' : 'Add' }} Party Sale</h2>

    <form action="{{ isset($partySale) ? route('party-sales.update', $partySale->id) : route('party-sales.store') }}" method="POST">
        @csrf
        @if(isset($partySale))
            @method('PUT')
        @endif

        <div class="mb-3">
            <label>Beat</label>
            <select name="beat_id" class="form-control">
                @foreach($beats as $beat)
                    <option value="{{ $beat->id }}" {{ (isset($partySale) && $partySale->beat_id==$beat->id) ? 'selected' : '' }}>
                        {{ $beat->name.'-('. $beat->salesman.')' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Customer Name</label>
            <select name="customer_id" class="form-control">
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" {{ (isset($partySale) && $partySale->customer_id==$customer->id) ? 'selected' : '' }}>
                        {{ $customer->name}}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Bill No</label>
            <input type="text" name="bill_no" class="form-control" value="{{ $partySale->bill_no ?? old('bill_no') }}">
        </div>

        <div class="mb-3">
            <label>Bill Date</label>
            <input type="date" name="bill_date" class="form-control" value="{{ isset($partySale->bill_date) ? $partySale->bill_date->format('Y-m-d') : '' }}">
        </div>

        {{-- <div class="mb-3"><label>Aging</label><input type="text" name="aging" class="form-control" value="{{ $partySale->aging ?? old('aging') }}"></div> --}}
        <div class="mb-3"><label>Amount</label><input type="number" step="0.01" name="amount" class="form-control" value="{{ $partySale->amount ?? old('amount') }}"></div>
        <div class="mb-3"><label>CD</label><input type="text" name="cd" class="form-control" value="{{ $partySale->cd ?? old('cd') }}"></div>
        <div class="mb-3"><label>Product Return</label><input type="text" name="product_return" class="form-control" value="{{ $partySale->product_return ?? old('product_return') }}"></div>
        <div class="mb-3"><label>Online Payment</label><input type="text" name="online_payment" class="form-control" value="{{ $partySale->online_payment ?? old('online_payment') }}"></div>
        <div class="mb-3"><label>Amount Received</label><input type="number" step="0.01" name="amount_received" class="form-control" value="{{ $partySale->amount_received ?? old('amount_received') }}"></div>
        {{-- <div class="mb-3"><label>Balance</label><input type="number" step="0.01" name="balance" class="form-control" value="{{ $partySale->balance ?? old('balance') }}"></div> --}}
        <div class="mb-3"><label>Remarks</label><input type="text" step="0.01" name="remarks" class="form-control" value="{{ $partySale->remarks ?? old('remarks') }}"></div>

        <button class="btn btn-primary" type="submit">{{ isset($partySale) ? 'Update' : 'Save' }}</button>
    </form>
</div>
@endsection
