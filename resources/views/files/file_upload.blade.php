 @extends('layouts.master')

@section('title', 'Excel Upload')

@section('content')

<div class="row justify-content-center">
    <div class="col-md-6">

        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Upload Excel File</h5>
            </div>

            <div class="card-body">

                {{-- Validation Errors --}}
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Session Error --}}
                @if(session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                <form action="{{ route('upload.excel') }}"
                      method="POST"
                      enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Select Excel File</label>
                        <input type="file"
                               name="excel_file"
                               class="form-control"
                               required>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-success">
                            Upload & Process
                        </button>

                        <a href="{{ route('beats.index') }}" class="btn btn-secondary">
                            Manage Beats
                        </a>
                    </div>
                </form>

            </div>
        </div>

    </div>
</div>

@endsection
