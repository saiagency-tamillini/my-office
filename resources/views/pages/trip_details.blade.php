{{-- @extends('layouts.master')
@include('modals.denomination_modal')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/party_sales.css') }}">
@endpush

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">Trip Details</h2>
        </div>

        <form method="GET" action="{{ route('trip.details') }}" class="row g-2 align-items-end mb-3">
            <div class="col-md-4">
                <label class="form-label fw-bold">Trip Date</label>
                <input type="date" id="tripDateInput" name="trip_date" class="form-control" value="{{ $selectedDate }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Route</label>
                <select id="routeSelect" name="route_id" class="form-select" required data-selected-route="{{ $selectedRouteId }}">
                    <option value="">-- Select Route --</option>
                    @foreach($routes as $route)
                        <option value="{{ $route->id }}" {{ (int) $selectedRouteId === (int) $route->id ? 'selected' : '' }}>
                            {{ $route->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </div>
        </form>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card">
            <div class="card-header fw-semibold">
                Trip Items
                @if($selectedRouteId)
                    <span class="ms-2 text-muted small">
                        Trips: {{ $tripCount }} | Items: {{ $tripItems->count() }}
                    </span>
                @endif
            </div>
            <div class="card-body p-0">
                @if(!$selectedRouteId)
                    <div class="text-center text-muted py-3">
                        Select date and route to view trip items.
                    </div>
                @elseif($tripCount === 0)
                    <div class="text-center text-muted py-3">
                        No trips found for selected date and route.
                    </div>
                @elseif($tripItems->isEmpty())
                    <div class="text-center text-muted py-3">
                        Trips found, but no trip items available.
                    </div>
                @else
                    <form id="tripDetailsForm" method="POST" action="{{ route('trip.details.update') }}">
                        @csrf
                        <input type="hidden" name="trip_date" value="{{ $selectedDate }}">
                        <input type="hidden" name="route_id" value="{{ $selectedRouteId }}">

                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Trip No</th>
                                        <th>Type</th>
                                        <th style="min-width: 220px;">Customer</th>
                                        <th>Bill No</th>
                                        <th>Bill Date</th>
                                        <th>Aging<br>(days)</th>
                                        <th>Amount</th>
                                        <th>CD</th>
                                        <th>Product Return</th>
                                        <th>Online Payment</th>
                                        <th>Amount Received</th>
                                        <th class="hide-print">Balance</th>
                                        <th class="hide-print">Beat</th>
                                        <th class="hide-print">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $serial = 1; @endphp
                                    @foreach($tripItems as $item)
                                        @php
                                            $locked = !empty($item->first_entry);
                                            $isCredit = $item->item_type === 'Credit';
                                            $billDate = $item->bill_date ? \Carbon\Carbon::parse($item->bill_date) : null;
                                            $aging = $billDate ? $billDate->diffInDays(\Carbon\Carbon::today(), false) : '';
                                            $cdVal = $isCredit ? ($item->credit_cd ?? 0) : ($item->party_cd ?? '');
                                            $prVal = $isCredit ? ($item->credit_product_return ?? '') : ($item->party_product_return ?? '');
                                            $opVal = $isCredit ? ($item->credit_online_payment ?? '') : ($item->party_online_payment ?? '');
                                            $arVal = $isCredit ? ($item->credit_amount_received ?? '') : ($item->party_amount_received ?? '');
                                            $balRef = $isCredit ? ($item->payment_balance ?? '') : ($item->sale_balance ?? '');
                                        @endphp
                                        <tr class="{{ $locked ? 'bg-lite' : '' }}">
                                            <td>
                                                {{ $serial++ }}
                                                @if(!$locked)
                                                    <input type="hidden" name="items[{{ $item->id }}][party_sale_id]" value="{{ $item->party_sale_id }}">
                                                    @if($isCredit && $item->payment_entry_id)
                                                        <input type="hidden" name="items[{{ $item->id }}][payment_entry_id]" value="{{ $item->payment_entry_id }}">
                                                    @endif
                                                @endif
                                            </td>
                                            <td>{{ $item->trip_number ?? '-' }}</td>
                                            <td>{{ $item->item_type }}</td>
                                            <td class="customer-name">
                                                @if($isCredit)
                                                    {{ $item->customer_name ?? '-' }}
                                                @else
                                                    @if($locked)
                                                        {{ $item->customer_name ?? '-' }}
                                                    @else
                                                        <select name="items[{{ $item->id }}][customer_id]" class="form-control w-100 customer-select">
                                                            @foreach($customers as $customer)
                                                                <option value="{{ $customer->id }}"
                                                                    {{ (int) $item->customer_id === (int) $customer->id ? 'selected' : '' }}>
                                                                    {{ $customer->name }} ({{ $customer->beat->name ?? 'No Beat' }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    @endif
                                                @endif
                                            </td>
                                            <td class="bill-no">{{ $item->bill_no ?? '-' }}</td>
                                            <td class="date-col">
                                                {{ $item->bill_date ? \Carbon\Carbon::parse($item->bill_date)->format('d-m-Y') : '' }}
                                                @if(!$isCredit && !$locked)
                                                    <input type="hidden" name="items[{{ $item->id }}][bill_date]" value="{{ $item->bill_date }}">
                                                @endif
                                            </td>
                                            <td class="aging-col">{{ $aging }}</td>
                                            <td>{{ $item->sale_amount ?? '-' }}</td>
                                            <td>
                                                @if($locked)
                                                    {{ $cdVal }}
                                                @else
                                                    <input type="number" class="form-control"
                                                        name="items[{{ $item->id }}][cd]"
                                                        value="{{ $cdVal }}"
                                                        max="{{ $item->sale_amount ?? 0 }}"
                                                        oninput="validateMax(this, {{ $balRef }}); updateBalanceTripItem({{ $item->id }}, {{ $balRef }})">
                                                @endif
                                            </td>
                                            <td>
                                                @if($locked)
                                                    {{ $prVal }}
                                                @else
                                                    <input type="number" class="form-control"
                                                        name="items[{{ $item->id }}][product_return]"
                                                        value="{{ $prVal }}"
                                                        max="{{ $item->sale_amount ?? 0 }}"
                                                        oninput="validateMax(this, {{ $balRef }}); updateBalanceTripItem({{ $item->id }}, {{ $balRef }})">
                                                @endif
                                            </td>
                                            <td class="amount-col">
                                                @if($locked)
                                                    {{ $opVal }}
                                                @else
                                                    <input type="number" class="form-control"
                                                        name="items[{{ $item->id }}][online_payment]"
                                                        value="{{ $opVal }}"
                                                        oninput="updateBalanceTripItem({{ $item->id }}, {{ $balRef }})">
                                                @endif
                                            </td>
                                            <td class="amount-col">
                                                @if($locked)
                                                    {{ $arVal }}
                                                @else
                                                    <input type="number" class="form-control"
                                                        name="items[{{ $item->id }}][amount_received]"
                                                        value="{{ $arVal }}"
                                                        oninput="updateBalanceTripItem({{ $item->id }}, {{ $balRef }})">
                                                @endif
                                            </td>
                                            <td class="hide-print balance-col">
                                                @if($locked)
                                                    {{ $item->display_balance ?? '-' }}
                                                @else
                                                    <input type="number" class="form-control balance"
                                                        style="width: 100px;"
                                                        id="balance-ti-{{ $item->id }}"
                                                        name="items[{{ $item->id }}][balance]"
                                                        data-amount="{{ $balRef }}"
                                                        value="{{ $item->display_balance }}"
                                                        readonly>
                                                @endif
                                            </td>
                                            <td class="hide-print">{{ $item->beat_name ?? '-' }}</td>
                                            <td class="hide-print">{{ $item->payment_status ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="hide-print">
                                    <tr style="font-weight:bold; background-color:#f0f0f0;">
                                        <td colspan="9" class="text-end">Total:</td>
                                        <td id="totalProductReturn">{{ $totalProductReturn }}</td>
                                        <td id="totalOnlinePayment">{{ $totalOnlinePayment }}</td>
                                        <td id="totalAmountReceived">{{ $prevTotalAmountReceived }}</td>
                                        <td id="totalBalance">{{ $totalBalance }}</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="text-end mt-3 mb-3 me-3">
                            <button type="submit" class="btn btn-success">Save Changes</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

    </div>
@endsection --}}
@extends('layouts.master')
@include('modals.denomination_modal')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/party_sales.css') }}">
@endpush

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="mb-0">Trip Details</h2>
            <button type="button" class="btn btn-info mb-3" onclick="printPage()">Print</button>
        </div>

        <div class="mb-3">
            <button class="btn btn-outline-primary w-100 text-start d-flex justify-content-between align-items-center"
                    type="button" data-bs-toggle="collapse" data-bs-target="#filterPanel"
                    aria-expanded="false" aria-controls="filterPanel">
                Filters
                <i class="bi bi-chevron-down" id="filterIcon"></i>
            </button>

            <div class="collapse mt-2" id="filterPanel">
                <form method="GET" action="{{ route('trip.details') }}" class="mb-3">
                    <div class="d-flex flex-column p-3 border rounded bg-light">
                        <div class="d-flex gap-5 flex-wrap">

                            <!-- Trip Date -->
                            <div class="mb-2 d-flex h-fit gap-2 align-items-center flex-wrap">
                                <label class="form-label fw-bold mb-0">Trip Date:</label>
                                <input type="date"
                                       id="tripDateInput"
                                       name="trip_date"
                                       class="form-control"
                                       value="{{ $selectedDate }}"
                                       required>
                            </div>

                            <!-- Route -->
                            <div>
                                <label class="form-label fw-bold">Route:</label>
                                <select id="routeSelect" name="route_id" class="form-select" required>
                                    <option value="">-- Select Route --</option>
                                    @foreach($routes as $route)
                                        <option value="{{ $route->id }}"
                                            {{ (int) $selectedRouteId === (int) $route->id ? 'selected' : '' }}>
                                            {{ $route->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Salesman -->
                            <div class="d-flex flex-wrap h-fit flex-column mb-2">
                                <div class="fw-bold">Filter by Salesman:</div>
                                @foreach($salesmen as $salesman)
                                    <div class="form-check me-3 cursor-pointer">
                                        <input class="form-check-input border border-dark"
                                               type="checkbox"
                                               name="salesmen[]"
                                               value="{{ $salesman }}"
                                               id="salesman_{{ $loop->index }}"
                                               {{ is_array(request('salesmen')) && in_array($salesman, request('salesmen')) ? 'checked' : '' }}>
                                        <label class="form-check-label cursor-pointer" for="salesman_{{ $loop->index }}">
                                            {{ $salesman }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>

                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary me-2">Filter</button>
                            <a href="{{ route('trip.details') }}" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <div id="printArea">
            <div class="card">
                <div class="card-header fw-semibold">
                    Trip Items
                    @if($selectedRouteId)
                        <span class="ms-2 text-muted small">
                            Route: {{ $route_name }} | Items: {{ $tripItems->count() }}
                        </span>
                    @endif
                </div>
                    <div class="card-body p-0">
                        @if(!$selectedRouteId)
                            <div class="text-center text-muted py-3">
                                Select date and route to view trip items.
                            </div>
                        @elseif($tripCount === 0)
                            <div class="text-center text-muted py-3">
                                No trips found for selected date and route.
                            </div>
                        @elseif($tripItems->isEmpty())
                            <div class="text-center text-muted py-3">
                                Trips found, but no trip items available.
                            </div>
                        @else
                            <form id="tripDetailsForm" method="POST" action="{{ route('trip.details.update') }}">
                                @csrf
                                <input type="hidden" name="trip_date" value="{{ $selectedDate }}">
                                <input type="hidden" name="route_id" value="{{ $selectedRouteId }}">

                                @php
                                    $saleItems = $tripItems->filter(fn($i) => $i->item_type !== 'Credit');
                                    $creditItems = $tripItems->filter(fn($i) => $i->item_type === 'Credit');
                                    $serial = 1;
                                    $currentSalesman = null;
                                @endphp

                                <div class="table-responsive">
                                    <table class="table table-bordered mb-0">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                {{-- <th>Trip No</th> --}}
                                                <th class="hide-print">Type</th>
                                                <th style="min-width: 220px;">Customer</th>
                                                <th>Bill No</th>
                                                <th class="hide-print">Bill Date</th>
                                                <th class="hide-print">Aging<br>(days)</th>
                                                <th>Amount</th>
                                                <th>CD</th>
                                                <th>Product Return</th>
                                                <th>Online Payment</th>
                                                <th>Amount Received</th>
                                                <th class="hide-print">Balance</th>
                                                {{-- <th class="hide-print">Beat</th> --}}
                                                {{-- <th class="hide-print">Status</th> --}}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($saleItems as $item)
                                                @php
                                                    $locked = !empty($item->first_entry) || $sheetLocked;
                                                    $isCredit = false;
                                                    $billDate = $item->bill_date ? \Carbon\Carbon::parse($item->bill_date) : null;
                                                    $aging = $billDate ? $billDate->diffInDays(\Carbon\Carbon::today(), false) : '';
                                                    $cdVal = $item->party_cd ?? '';
                                                    $prVal = $item->party_product_return ?? '';
                                                    $opVal = $item->party_online_payment ?? '';
                                                    $arVal = $item->party_amount_received ?? '';
                                                    $balRef = $item->sale_balance ?? '';
                                                    $salesmanName = $item->salesman_name ?? 'No Salesman';
                                                @endphp

                                                @if($currentSalesman !== $salesmanName)
                                                    <tr class="salesman-row">
                                                        <td colspan="15" class="salesman-cell">{{ $salesmanName }}</td>
                                                    </tr>
                                                    @php
                                                        $currentSalesman = $salesmanName;
                                                        $serial = 1;
                                                    @endphp
                                                @endif

                                                <tr class="{{ $locked ? 'bg-lite' : '' }}">
                                                    <td>
                                                        {{ $serial++ }}
                                                        @if(!$locked)
                                                            <input type="hidden" name="items[{{ $item->id }}][party_sale_id]" value="{{ $item->party_sale_id }}">
                                                        @endif
                                                    </td>

                                                    {{-- <td>{{ $item->trip_number ?? '-' }}</td> --}}
                                                    <td class="hide-print">{{ $item->item_type }}</td>

                                                    <td class="customer-name">
                                                        @if($locked)
                                                            {{ $item->customer_name ?? '-' }}
                                                        @else
                                                            <select name="items[{{ $item->id }}][customer_id]" class="form-control w-100 customer-select">
                                                                @foreach($customers as $customer)
                                                                    <option value="{{ $customer->id }}"
                                                                        {{ (int) $item->customer_id === (int) $customer->id ? 'selected' : '' }}>
                                                                        {{ $customer->name }} ({{ $customer->beat->name ?? 'No Beat' }})
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        @endif
                                                    </td>

                                                    <td class="bill-no bold-font">{{ $item->bill_no ?? '-' }}</td>

                                                    <td class="date-col hide-print">
                                                        {{ $item->bill_date ? \Carbon\Carbon::parse($item->bill_date)->format('d-m-Y') : '' }}
                                                        @if(!$locked)
                                                            <input type="hidden" name="items[{{ $item->id }}][bill_date]" value="{{ $item->bill_date }}">
                                                        @endif
                                                    </td>

                                                    <td class="aging-col hide-print">{{ $aging }}</td>
                                                    <td class="bold-font">{{ $item->sale_amount ?? '-' }}</td>

                                                    <td>
                                                        @if($locked)
                                                            {{ $cdVal }}
                                                        @else
                                                            <input type="number" class="form-control"
                                                                name="items[{{ $item->id }}][cd]"
                                                                value="{{ $cdVal }}"
                                                                max="{{ $item->sale_amount ?? 0 }}"
                                                                oninput="validateMax(this, {{ $balRef }}); updateBalanceTripItem({{ $item->id }}, {{ $balRef }})">
                                                        @endif
                                                    </td>

                                                    <td>
                                                        @if($locked)
                                                            {{ $prVal }}
                                                        @else
                                                            <input type="number" class="form-control"
                                                                name="items[{{ $item->id }}][product_return]"
                                                                value="{{ $prVal }}"
                                                                max="{{ $item->sale_amount ?? 0 }}"
                                                                oninput="validateMax(this, {{ $balRef }}); updateBalanceTripItem({{ $item->id }}, {{ $balRef }})">
                                                        @endif
                                                    </td>

                                                    <td class="">
                                                        @if($locked)
                                                            {{ $opVal }}
                                                        @else
                                                            <input type="number" class="form-control"
                                                                name="items[{{ $item->id }}][online_payment]"
                                                                value="{{ $opVal }}"
                                                                oninput="updateBalanceTripItem({{ $item->id }}, {{ $balRef }})">
                                                        @endif
                                                    </td>

                                                    <td class="amount-col">
                                                        @if($locked)
                                                            {{ $arVal }}
                                                        @else
                                                            <input type="number" class="form-control"
                                                                name="items[{{ $item->id }}][amount_received]"
                                                                value="{{ $arVal }}"
                                                                oninput="updateBalanceTripItem({{ $item->id }}, {{ $balRef }})">
                                                        @endif
                                                    </td>

                                                    <td class="hide-print balance-col">
                                                        @if($locked)
                                                            {{ $item->display_balance ?? '-' }}
                                                        @else
                                                            <input type="number" class="form-control balance"
                                                                style="width: 100px;"
                                                                id="balance-ti-{{ $item->id }}"
                                                                name="items[{{ $item->id }}][balance]"
                                                                data-amount="{{ $balRef }}"
                                                                value="{{ $item->display_balance }}"
                                                                readonly>
                                                        @endif
                                                    </td>

                                                    {{-- <td class="hide-print">{{ $item->beat_name ?? '-' }}</td> --}}
                                                    {{-- <td class="hide-print">{{ $item->payment_status ?? '-' }}</td> --}}
                                                </tr>
                                            @endforeach

                                            @if($creditItems->isNotEmpty())
                                                <tr class="salesman-row credit-header">
                                                    <td colspan="12" class="salesman-cell text-center">CREDITS</td>
                                                </tr>
                                                @php $serial = 1; @endphp
                                                @foreach($creditItems as $item)
                                                    @php
                                                        $locked = $sheetLocked;
                                                        $billDate = $item->bill_date ? \Carbon\Carbon::parse($item->bill_date) : null;
                                                        $aging = $billDate ? $billDate->diffInDays(\Carbon\Carbon::today(), false) : '';
                                                        $cdVal = $sheetLocked ? ($item->credit_cd ?? 0) : '';
                                                        $prVal = $sheetLocked ? ($item->credit_product_return ?? '') : '';
                                                        $opVal = $sheetLocked ? ($item->credit_online_payment ?? '') : '';
                                                        $arVal = $sheetLocked ? ($item->credit_amount_received ?? '') : '';
                                                        $balRef = $item->payment_balance ?? '';
                                                    @endphp

                                                    <tr class="{{ $locked ? 'bg-lite' : '' }}">
                                                        <td>
                                                            {{ $serial++ }}
                                                            @if(!$locked)
                                                                <input type="hidden" name="items[{{ $item->id }}][party_sale_id]" value="{{ $item->party_sale_id }}">
                                                                @if($item->payment_entry_id)
                                                                    <input type="hidden" name="items[{{ $item->id }}][payment_entry_id]" value="{{ $item->payment_entry_id }}">
                                                                @endif
                                                            @endif
                                                        </td>

                                                        <td class="hide-print">{{ $item->item_type }}</td>

                                                        <td class="customer-name">
                                                            {{ $item->customer_name ?? '-' }}
                                                        </td>

                                                        <td class="bill-no bold-font">{{ $item->bill_no ?? '-' }}</td>

                                                        <td class="date-col hide-print">
                                                            {{ $item->bill_date ? \Carbon\Carbon::parse($item->bill_date)->format('d-m-Y') : '' }}
                                                        </td>

                                                        <td class="aging-col hide-print">{{ $aging }}</td>
                                                        <td class="bold-font">{{ $item->sale_amount ?? '-' }}</td>

                                                        <td>
                                                            @if($locked)
                                                                {{ $cdVal }}
                                                            @else
                                                                <input type="number" class="form-control"
                                                                    name="items[{{ $item->id }}][cd]"
                                                                    value="{{ $cdVal }}"
                                                                    max="{{ $item->sale_amount ?? 0 }}"
                                                                    oninput="validateMax(this, {{ $balRef }}); updateBalanceTripItem({{ $item->id }}, {{ $balRef }})">
                                                            @endif
                                                        </td>

                                                        <td>
                                                            @if($locked)
                                                                {{ $prVal }}
                                                            @else
                                                                <input type="number" class="form-control"
                                                                    name="items[{{ $item->id }}][product_return]"
                                                                    value="{{ $prVal }}"
                                                                    max="{{ $item->sale_amount ?? 0 }}"
                                                                    oninput="validateMax(this, {{ $balRef }}); updateBalanceTripItem({{ $item->id }}, {{ $balRef }})">
                                                            @endif
                                                        </td>

                                                        <td class="">
                                                            @if($locked)
                                                                {{ $opVal }}
                                                            @else
                                                                <input type="number" class="form-control"
                                                                    name="items[{{ $item->id }}][online_payment]"
                                                                    value="{{ $opVal }}"
                                                                    oninput="updateBalanceTripItem({{ $item->id }}, {{ $balRef }})">
                                                            @endif
                                                        </td>

                                                        <td class="amount-col">
                                                            @if($locked)
                                                                {{ $arVal }}
                                                            @else
                                                                <input type="number" class="form-control"
                                                                    name="items[{{ $item->id }}][amount_received]"
                                                                    value="{{ $arVal }}"
                                                                    oninput="updateBalanceTripItem({{ $item->id }}, {{ $balRef }})">
                                                            @endif
                                                        </td>

                                                        <td class="hide-print balance-col">
                                                            @if($locked)
                                                                {{ $item->display_balance ?? '-' }}
                                                            @else
                                                                <input type="number" class="form-control balance"
                                                                    style="width: 100px;"
                                                                    id="balance-ti-{{ $item->id }}"
                                                                    name="items[{{ $item->id }}][balance]"
                                                                    data-amount="{{ $balRef }}"
                                                                    value="{{ $item->display_balance }}"
                                                                    readonly>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        </tbody>

                                        <tfoot class="hide-print">
                                            <tr style="font-weight:bold; background-color:#f0f0f0;">
                                                <td colspan="8" class="text-end">Total:</td>
                                                <td id="totalProductReturn">{{ $totalProductReturn }}</td>
                                                <td id="totalOnlinePayment">{{ $totalOnlinePayment }}</td>
                                                <td id="totalAmountReceived">{{ $prevTotalAmountReceived }}</td>
                                                <td id="totalBalance">{{ $totalBalance }}</td>
                                                {{-- <td colspan="1"></td> --}}
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                @if(!$sheetLocked)
                                    <div class="d-flex justify-content-between align-items-center mt-3 mb-3 mx-3 hide-print">
                                        <div>
                                            @if(!empty($trip_number))
                                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteTripSheetModal">
                                                    <i class="bi bi-trash"></i> Delete Trip Sheet
                                                </button>
                                            @endif
                                        </div>
                                        <button type="submit" class="btn btn-success">Save Changes</button>
                                    </div>
                                @else
                                    <div class="text-center mt-3 mb-3 hide-print">
                                        <span class="badge bg-secondary fs-6">This trip sheet has been saved and is locked for editing.</span>
                                    </div>
                                @endif
                            </form>
                            @if(!$sheetLocked && !empty($trip_number))
                                <div class="modal fade" id="deleteTripSheetModal" tabindex="-1" aria-labelledby="deleteTripSheetModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title" id="deleteTripSheetModalLabel">Delete Trip Sheet</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form method="POST" action="{{ route('trip.details.delete') }}">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="trip_date" value="{{ $selectedDate }}">
                                                <input type="hidden" name="route_id" value="{{ $selectedRouteId }}">
                                                <input type="hidden" name="trip_number" value="{{ $trip_number }}">
                                                <div class="modal-body">
                                                    <p class="mb-2">Delete trip for this date <strong>{{ $selectedDate }}</strong> and route <strong>{{ $route_name }}</strong>?</p>
                                                    <p class="text-danger mb-0"><strong>Warning:</strong> This will permanently delete this trip and all trip items. This action cannot be undone.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger">Yes, Delete</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        const tripDateInput = document.getElementById('tripDateInput');
        const routeSelect = document.getElementById('routeSelect');

        function setRouteOptions(routes, selectedRouteId = '') {
            routeSelect.innerHTML = '<option value="">-- Select Route --</option>';

            routes.forEach(route => {
                const option = document.createElement('option');
                option.value = route.id;
                option.textContent = route.name;

                if (String(selectedRouteId) === String(route.id)) {
                    option.selected = true;
                }

                routeSelect.appendChild(option);
            });
        }

        async function loadRoutesForDate(selectedRouteId = '') {
            const tripDate = tripDateInput.value;
            if (!tripDate) {
                setRouteOptions([]);
                return;
            }

            routeSelect.disabled = true;

            try {
                const response = await fetch(`{{ route('trip.details.routes') }}?trip_date=${encodeURIComponent(tripDate)}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Unable to load routes');
                }

                setRouteOptions(result.routes || [], selectedRouteId);
            } catch (error) {
                setRouteOptions([]);
                alert(error.message || 'Failed to load routes');
            } finally {
                routeSelect.disabled = false;
            }
        }

        tripDateInput?.addEventListener('change', function () {
            loadRoutesForDate('');
        });

        document.addEventListener('DOMContentLoaded', function () {
            loadRoutesForDate(routeSelect.dataset.selectedRoute || '');
        });

        function validateMax(input, maxValue) {
            let value = parseFloat(input.value) || 0;
            if (value > maxValue) {
                input.value = maxValue;
                alert('Value cannot exceed ' + maxValue);
            } else if (value < 0) {
                input.value = 0;
            }
        }

        function updateBalanceTripItem(tripItemId, amount) {
            const cdEl = document.querySelector(`input[name='items[${tripItemId}][cd]']`);
            const productReturnInput = document.querySelector(`input[name='items[${tripItemId}][product_return]']`);
            const onlinePaymentInput = document.querySelector(`input[name='items[${tripItemId}][online_payment]']`);
            const amountReceivedInput = document.querySelector(`input[name='items[${tripItemId}][amount_received]']`);
            if (!cdEl || !productReturnInput || !onlinePaymentInput || !amountReceivedInput) return;

            const cd = parseFloat(cdEl.value) || 0;
            const productReturn = parseFloat(productReturnInput.value) || 0;
            const onlinePayment = parseFloat(onlinePaymentInput.value) || 0;
            const amountReceived = parseFloat(amountReceivedInput.value) || 0;

            let balance = amount - (cd + productReturn + onlinePayment + amountReceived);
            const balanceInput = document.getElementById(`balance-ti-${tripItemId}`);
            if (!balanceInput) return;

            if (balance < 0) {
                balanceInput.style.border = "2px solid green";
                balanceInput.style.color = "green";
            } else {
                balanceInput.style.border = "";
                balanceInput.style.color = "black";
            }
            balanceInput.value = balance;
            updateTotalsTripDetails();
        }

        function updateTotalsTripDetails() {
            let totalProductReturn = 0;
            let totalOnlinePayment = 0;
            let totalAmountReceived = 0;
            let totalBalance = 0;

            document.querySelectorAll("#tripDetailsForm input[name$='[product_return]']").forEach(input => {
                totalProductReturn += parseFloat(input.value) || 0;
            });
            document.querySelectorAll("#tripDetailsForm input[name$='[online_payment]']").forEach(input => {
                totalOnlinePayment += parseFloat(input.value) || 0;
            });
            document.querySelectorAll("#tripDetailsForm input[name$='[amount_received]']").forEach(input => {
                totalAmountReceived += parseFloat(input.value) || 0;
            });
            document.querySelectorAll("#tripDetailsForm input.balance").forEach(input => {
                totalBalance += parseFloat(input.value) || 0;
            });

            const tpr = document.getElementById('totalProductReturn');
            const top = document.getElementById('totalOnlinePayment');
            const tar = document.getElementById('totalAmountReceived');
            const tb = document.getElementById('totalBalance');
            if (tpr) tpr.textContent = totalProductReturn;
            if (top) top.textContent = totalOnlinePayment;
            if (tar) tar.textContent = totalAmountReceived;
            if (tb) tb.textContent = totalBalance;
        }

        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('tripDetailsForm');
            if (!form) return;

            const PrevTotalAmount = {{ (int) $prevTotalAmountReceived }};

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const denominationModal = new bootstrap.Modal(document.getElementById('denominationModal'));
                denominationModal.show();
            });

            document.getElementById('submitWithDenomination')?.addEventListener('click', function () {
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

        $(document).on('focus', '#tripDetailsForm .customer-select', function () {
            if ($(this).hasClass('select2-hidden-accessible')) return;

            $(this).select2({
                width: '100%',
                placeholder: 'Type to search customer...',
                allowClear: false
            });
        });
        function printPage() {
            window.print();
        }
    </script>
@endpush
