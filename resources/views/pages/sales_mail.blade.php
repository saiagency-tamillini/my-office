@extends('layouts.master')

@section('content')
<div class="container">

    <h3 class="mb-4">Send Sales Report Mail</h3>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
   @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <table class="table table-borderless align-middle">
        <thead class="table-light">
            <tr>
                <th>Salesman</th>
                <th>Beats</th>
                <th>From Date</th>
                <th>To Date</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>

            @foreach($salesmen as $salesman)

            <tr>

                <td style="width:180px;">
                    <strong>{{ $salesman }}</strong>
                </td>

                <td colspan="4">
                    <form action="{{ route('sales.mail.send') }}" method="POST" class="salesman-mail-form">
                        @csrf

                        <input type="hidden" name="salesmen[]" value="{{ $salesman }}">

                        <div class="selected-beats-container"></div>

                        <div class="row align-items-center">

                            <div class="col-md-5">
                                <div class="d-flex flex-wrap gap-2">

                                    @foreach($beats->where('salesman', $salesman) as $beat)
                                        <div class="form-check">
                                            <input type="checkbox"
                                                class="form-check-input global-beat-checkbox"
                                                value="{{ $beat->id }}"
                                                id="beat_{{ $salesman }}_{{ $beat->id }}">

                                            <label class="form-check-label"
                                                for="beat_{{ $salesman }}_{{ $beat->id }}">
                                                {{ $beat->name }}
                                            </label>
                                        </div>
                                    @endforeach

                                </div>
                            </div>

                            <div class="col-md-2">
                                <input type="date"
                                    name="from_date"
                                    class="form-control"
                                    required>
                            </div>

                            <div class="col-md-2">
                                <input type="date"
                                    name="to_date"
                                    class="form-control">
                            </div>

                            <div class="col-md-2">
                                <button type="submit"
                                        class="btn btn-primary btn-sm send-mail-btn">
                                    <i class="bi bi-envelope-fill"></i>
                                    Send Mail
                                </button>
                            </div>

                        </div>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>
@endsection
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {

        const forms = document.querySelectorAll('form');

        forms.forEach(form => {

            form.addEventListener('submit', function () {
                // clear previous hidden inputs
                const container = form.querySelector('.selected-beats-container');
                if (!container) {
                    console.error('selected-beats-container missing');
                    return;
                }
                container.innerHTML = '';
                // get all checked beats from entire page
                const checkedBeats = document.querySelectorAll('.global-beat-checkbox:checked');
                checkedBeats.forEach(checkbox => {

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'beat_ids[]';
                    input.value = checkbox.value;

                    container.appendChild(input);
                });
                // disable all buttons
                document.querySelectorAll('.send-mail-btn').forEach(btn => {
                    btn.disabled = true;
                    btn.innerHTML = 'Sending...';
                });

            });

        });

    });
</script>
@endpush