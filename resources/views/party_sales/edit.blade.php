@extends('layouts.master')

@section('content')
<script>
    window.IS_MANUAL_BILL = @json($isManualBill);
    window.HAS_EXISTING_ITEMS = @json($hasExistingItems);
</script>
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

    @php
        // product ids already added in this party sale
        $existingProductIds = $partySale->manualItems->pluck('product_id')->toArray();
    @endphp

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
            <label class="form-label">Customer Name</label>
            <select name="customer_id" class="form-control customer-select" required>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}"
                        {{ old('customer_id', $partySale->customer_id ?? '') == $customer->id ? 'selected' : '' }}>
                        {{ $customer->name }} ({{ $customer->beat->name ?? '' }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="bill_no" class="form-label">Bill No</label>
            <input type="text" name="bill_no" id="bill_no" class="form-control" value="{{ $partySale->bill_no }}" readonly>
        </div>

        <div class="mb-3">
            <label for="bill_date" class="form-label">Bill Date</label>
            <input type="date" name="bill_date" id="bill_date" class="form-control"
                   value="{{ $partySale->bill_date ? $partySale->bill_date->format('Y-m-d') : '' }}">
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
            <input type="number" step="0.01" name="amount_received" id="amount_received" class="form-control" value="{{ $partySale->amount_received }}">
        </div>

        <div class="mb-3">
            <label for="remarks" class="form-label">Remarks</label>
            <input type="text" name="remarks" id="remarks" class="form-control" value="{{ $partySale->remarks }}">
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox"
                name="modified"
                id="modified"
                class="form-check-input"
                value="1"
                {{ $partySale->modified ? 'checked' : '' }}>
            <label class="form-check-label" for="modified">
                Mark as Modified
            </label>
        </div>
        @if($isManualBill)
            {{-- ✅ Products section --}}
            <h5 class="mt-4">Products</h5>

            <div class="row g-2 align-items-end mb-3">
                <div class="col-md-8">
                    <label class="form-label">Select Product</label>
                    <select id="productDropdown" class="form-control">
                        <option value="">-- Select a product --</option>
                        @foreach($products as $product)
                            @if(!in_array($product->id, $existingProductIds))
                                <option
                                    value="{{ $product->id }}"
                                    data-box-amount="{{ $product->box_amount }}"
                                    data-piece-amount="{{ $product->piece_amount }}"
                                >
                                    {{ $product->name }}
                                </option>
                            @endif
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
                        <th>Product</th>
                        <th>Box</th>
                        <th>Pcs</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($partySale->manualItems as $index => $item)
                        <tr
                            data-product-id="{{ $item->product_id }}"
                            data-box-amount="{{ $item->product->box_amount ?? 0 }}"
                            data-piece-amount="{{ $item->product->piece_amount ?? 0 }}"
                        >
                            <td>
                                {{ $item->product->name ?? 'Product Deleted' }}
                                <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                            </td>
                            <td>
                                <input type="number" min="0"
                                    name="items[{{ $index }}][box]"
                                    class="form-control box-qty"
                                    value="{{ $item->box }}">
                            </td>
                            <td>
                                <input type="number" min="0"
                                    name="items[{{ $index }}][pcs]"
                                    class="form-control pcs-qty"
                                    value="{{ $item->pcs }}">
                            </td>
                            <td class="row-amount text-end">0.00</td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm remove-product">Remove</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        <button type="submit" class="btn btn-primary">Update</button>
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
            if (!window.IS_MANUAL_BILL) {
                return; 
            }
            // ✅ start after existing rows
            let itemIndex = $('#productsTable tbody tr').length;
            function recalculateGrandTotal() {
                let total = 0;
                $('.row-amount').each(function () {
                    total += parseFloat($(this).text()) || 0;
                });
                $('#amount').val(total.toFixed(2));
            }
            function calculateRow($row) {
                const boxQty = parseFloat($row.find('.box-qty').val()) || 0;
                const pcsQty = parseFloat($row.find('.pcs-qty').val()) || 0;
                const boxAmount = parseFloat($row.data('box-amount')) || 0;
                const pieceAmount = parseFloat($row.data('piece-amount')) || 0;

                const total = (boxQty * boxAmount) + (pcsQty * pieceAmount);
                $row.find('.row-amount').text(total.toFixed(2));
            }
            $('#productsTable tbody tr').each(function () {
                calculateRow($(this));
            });
            if(HAS_EXISTING_ITEMS){
                recalculateGrandTotal();
            }
            $('#addProductBtn').on('click', function () {
                const $dd = $('#productDropdown');
                const productId = $dd.val();

                if (!productId) {
                    alert('Please select a product');
                    return;
                }

                const opt = $dd.find('option:selected');
                const productName = opt.text();
                const boxAmount = opt.data('box-amount');
                const pieceAmount = opt.data('piece-amount');

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
                    </tr>`;

                $('#productsTable tbody').append(row);
                $dd.find(`option[value="${productId}"]`).remove();
                $dd.val('');
                itemIndex++;

            });
            $(document).on('input', '.box-qty, .pcs-qty', function () {
                const $row = $(this).closest('tr');
                calculateRow($row);
                recalculateGrandTotal();
            });
            // remove row and add back to dropdown
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
