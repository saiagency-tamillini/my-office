@extends('layouts.master')

@section('content')
<style>
    .salesman-row { background:#f8f9fa; font-weight:600; }
    .beat-row { background:#ffffff; }
    .customer-row { background:#fcfcfc; }

    .indent-beat { padding-left: 30px; background:#e3bcff !important ; }
    .indent-customer { padding-left: 60px; background:#b9ffc3 !important; }

    .clickable { cursor: pointer; color:#0d6efd; }
    .clickable:hover { text-decoration: underline; }

    .badge-pending { background:#ffc107; }
    .badge-paid { background:#198754; }

    .toggle-icon { font-size: 14px; margin-right: 6px; }
</style>

<div class="container">
    <h3 class="mb-3">Salesman List</h3>

    <table class="table table-bordered align-middle">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Total Customers</th>
                <th>Total Pending</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @php $i = 1; @endphp
        @foreach($salesmen as $salesmanName => $data)
            {{-- Salesman --}}
            <tr class="salesman-row" data-salesman="{{ $i }}">
                <td>{{ $i }}</td>
                <td>
                    <span class="clickable toggle-beats">
                        <i class="bi bi-person toggle-icon"></i>{{ $salesmanName }}
                    </span>
                </td>
                <td>{{ $data['total_customers'] }}</td>
                <td>
                    <span class="badge {{ $data['total_pending'] > 0 ? 'badge-pending' : 'badge-paid' }}">
                        {{ $data['total_pending'] }}
                    </span>
                </td>
                <td></td>
            </tr>

            {{-- Beats --}}
            @foreach($data['beats'] as $beat)
                <tr class="beat-row beat-of-{{ $i }}" style="display:none" data-beat="{{ $beat->id }}">
                    <td></td>
                    <td class="indent-beat">
                        <span class="clickable toggle-customers ms-4">
                            <i class="bi bi-diagram-3 toggle-icon"></i>{{ $beat->name }}
                        </span>
                    </td>
                    <td>{{$beat->customers_count ?? 0 }}</td>
                    <td>
                        <span class="badge {{ $beat->pending > 0 ? 'badge-pending' : 'badge-paid' }}">
                            {{ $beat->pending }}
                        </span>
                    </td>
                    <td></td>
                </tr>

                {{-- Customers --}}
                @foreach($beat->customers as $customer)
                    <tr class="customer-row customer-of-{{ $beat->id }}" style="display:none">
                        <td></td>
                        <td class="indent-customer ms-3">
                            <i class="bi bi-person-circle toggle-icon ms-5"></i>{{ $customer->name }}
                        </td>
                        <td></td>
                        <td>
                            <span class="badge {{ $customer->pending > 0 ? 'badge-pending' : 'badge-paid' }}">
                                {{ $customer->pending }}
                            </span>
                        </td>
                        <td>
                            <form action="{{ route('salesman.reports') }}" method="POST">
                                @csrf
                                <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                                <button class="btn btn-sm btn-outline-primary">
                                    View Report
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            @endforeach
            @php $i++; @endphp
        @endforeach
        </tbody>
    </table>
</div>

{{-- <script>
    document.querySelectorAll('.toggle-beats').forEach(el => {
        el.addEventListener('click', () => {
            let salesmanId = el.closest('tr').dataset.salesman;
            document.querySelectorAll('.beat-of-' + salesmanId)
                .forEach(r => r.style.display = r.style.display === 'none' ? '' : 'none');
        });
    });

    document.querySelectorAll('.toggle-customers').forEach(el => {
        el.addEventListener('click', () => {
            let beatId = el.closest('tr').dataset.beat;
            document.querySelectorAll('.customer-of-' + beatId)
                .forEach(r => r.style.display = r.style.display === 'none' ? '' : 'none');
        });
    });
</script> --}}
<script>
document.querySelectorAll('.toggle-beats').forEach(el => {
    el.addEventListener('click', () => {

        let salesmanRow = el.closest('tr');
        let salesmanId = salesmanRow.dataset.salesman;

        // Toggle beats
        document.querySelectorAll('.beat-of-' + salesmanId)
            .forEach(beatRow => {
                const isHidden = beatRow.style.display === 'none';
                beatRow.style.display = isHidden ? '' : 'none';

                // 🔥 IMPORTANT: hide customers of this beat
                let beatId = beatRow.dataset.beat;
                document.querySelectorAll('.customer-of-' + beatId)
                    .forEach(cust => cust.style.display = 'none');
            });
    });
});

document.querySelectorAll('.toggle-customers').forEach(el => {
    el.addEventListener('click', () => {
        let beatId = el.closest('tr').dataset.beat;

        document.querySelectorAll('.customer-of-' + beatId)
            .forEach(r => {
                r.style.display = r.style.display === 'none' ? '' : 'none';
            });
    });
});
</script>

@endsection
