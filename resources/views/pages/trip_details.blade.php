@extends('layouts.master')

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
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Trip No</th>
                                <th>Type</th>
                                <th>Customer</th>
                                <th>Bill No</th>
                                <th>Bill Date</th>
                                <th>Beat</th>
                                <th>Amount</th>
                                <th>Received</th>
                                <th>Balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(!$selectedRouteId)
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-3">
                                        Select date and route to view trip items.
                                    </td>
                                </tr>
                            @elseif($tripCount === 0)
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-3">
                                        No trips found for selected date and route.
                                    </td>
                                </tr>
                            @elseif($tripItems->isEmpty())
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-3">
                                        Trips found, but no trip items available.
                                    </td>
                                </tr>
                            @else
                                @foreach($tripItems as $item)
                                    <tr>
                                        <td>{{ $item->trip_number ?? '-' }}</td>
                                        <td>{{ $item->item_type }}</td>
                                        <td>{{ $item->customer_name ?? '-' }}</td>
                                        <td>{{ $item->bill_no ?? '-' }}</td>
                                        <td>{{ $item->bill_date ? \Carbon\Carbon::parse($item->bill_date)->format('d-m-Y') : '-' }}</td>
                                        <td>{{ $item->beat_name ?? '-' }}</td>
                                        <td>{{ $item->sale_amount ?? '-' }}</td>
                                        <td>{{ $item->amount_received ?? '-' }}</td>
                                        <td>{{ $item->display_balance ?? '-' }}</td>
                                        <td>{{ $item->payment_status ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
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
    </script>
@endpush
