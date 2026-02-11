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
            <label>Customer Name</label>
            <select name="customer_id" class="form-control customer-select" required>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" {{ (isset($partySale) && $partySale->customer_id==$customer->id) ? 'selected' : '' }}>
                        {{ $customer->name.' ('.$customer->beat->name.')'}}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Bill Date</label>
            <input type="date" name="bill_date" class="form-control"
                value="{{ isset($partySale->bill_date) ? $partySale->bill_date->format('Y-m-d') : '' }}" required>
        </div>

        <div class="mb-3"><label>Amount</label><input type="number" step="0.01" name="amount" class="form-control" value="{{ $partySale->amount ?? old('amount') }}"></div>
        <div class="mb-3"><label>CD</label><input type="text" name="cd" class="form-control" value="{{ $partySale->cd ?? old('cd') }}"></div>
        <div class="mb-3"><label>Product Return</label><input type="text" name="product_return" class="form-control" value="{{ $partySale->product_return ?? old('product_return') }}"></div>
        <div class="mb-3"><label>Online Payment</label><input type="text" name="online_payment" class="form-control" value="{{ $partySale->online_payment ?? old('online_payment') }}"></div>
        <div class="mb-3"><label>Amount Received</label><input type="number" step="0.01" name="amount_received" class="form-control" value="{{ $partySale->amount_received ?? old('amount_received') }}"></div>
        <div class="mb-3"><label>Remarks</label><input type="text" name="remarks" class="form-control" value="{{ $partySale->remarks ?? old('remarks') }}"></div>

        {{-- ✅ Products section INSIDE form --}}
        <h5 class="mt-4">Products</h5>

        <div class="row g-2 align-items-end mb-3">
            <div class="col-md-8">
                <label class="form-label">Select Product</label>
                <select id="productDropdown" class="form-control">
                    <option value="">-- Select a product --</option>
                    @foreach($products as $product)
                        <option
                            value="{{ $product->id }}"
                            data-box-amount="{{ $product->box_amount }}"
                            data-piece-amount="{{ $product->piece_amount }}"
                        >
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <button type="button" id="addProductBtn" class="btn btn-success w-100">
                    + Add Product
                </button>
            </div>
        </div>

        <table class="table table-bordered" id="productsTable">
            <thead>
                <tr>
                    <th style="width:45%">Product</th>
                    <th style="width:15%">Box</th>
                    <th style="width:15%">Pcs</th>
                    <th style="width:15%">Amount</th>
                    <th style="width:25%">Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

        <button class="btn btn-primary" type="submit">{{ isset($partySale) ? 'Update' : 'Save' }}</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {

    $('.customer-select').select2({
        width: '100%',
        placeholder: 'Type to search customer...',
        allowClear: false
    });

    let itemIndex = 0;
    function recalculateGrandTotal() {
        let total = 0;

        $('.row-amount').each(function () {
            total += parseFloat($(this).text()) || 0;
        });

        $('input[name="amount"]').val(total.toFixed(2));
    }

    $('#addProductBtn').on('click', function () {
        const $dd = $('#productDropdown');
        const productId = $dd.val();

        if (!productId) {
            alert('Please select a product');
            return;
        }

        const option = $dd.find('option:selected');
        const productName = option.text();
        const boxAmount = parseFloat(option.data('box-amount'));
        const pieceAmount = parseFloat(option.data('piece-amount'));
        const row = `
            <tr data-product-id="${productId}"
                data-box-amount="${boxAmount}"
                data-piece-amount="${pieceAmount}">
                <td>
                    ${productName}
                    <input type="hidden" name="items[${itemIndex}][product_id]" value="${productId}">
                </td>
                <td>
                    <input type="number" min="0" value="0"
                        name="items[${itemIndex}][box]"
                        class="form-control box-qty">
                </td>
                <td>
                    <input type="number" min="0" value="0"
                        name="items[${itemIndex}][pcs]"
                        class="form-control pcs-qty">
                </td>
                <td class="row-amount text-end">0.00</td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-product">Remove</button>
                </td>
            </tr>
        `;

        $('#productsTable tbody').append(row);

        $dd.find(`option[value="${productId}"]`).remove();
        $dd.val('');

        itemIndex++;
    });
    $(document).on('input', '.box-qty, .pcs-qty', function () {
        const $row = $(this).closest('tr');

        const boxQty = parseFloat($row.find('.box-qty').val()) || 0;
        const pcsQty = parseFloat($row.find('.pcs-qty').val()) || 0;

        const boxAmount = parseFloat($row.data('box-amount'));
        const pieceAmount = parseFloat($row.data('piece-amount'));

        const rowTotal = (boxQty * boxAmount) + (pcsQty * pieceAmount);

        $row.find('.row-amount').text(rowTotal.toFixed(2));

        recalculateGrandTotal();
    });
    $(document).on('click', '.remove-product', function () {
        const $row = $(this).closest('tr');
        const productId = $row.data('product-id');
        const productName = $row.find('td:first').clone().children().remove().end().text().trim();

        $('#productDropdown').append(`<option value="${productId}">${productName}</option>`);
        $row.remove();
        recalculateGrandTotal();
    });

});
</script>
@endpush
