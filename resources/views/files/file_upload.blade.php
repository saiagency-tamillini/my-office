@extends('layouts.master')
@section('title', 'Excel Upload')
@section('content')
    @if(is_admin() || is_mis_access())
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
    @else
        <div class="container py-4">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-warning text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-shield-lock"></i>
                            <h5 class="mb-0">Access Restricted</h5>
                        </div>

                        <div class="card-body">
                            <div class="alert alert-warning mb-0">
                                <h6 class="mb-2">You don’t have permission to upload Excel files.</h6>
                                <p class="mb-3">
                                    Please contact the site admin <strong>Anand</strong> for access.
                                </p>

                                <div class="d-flex flex-column gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-dark">Mobile</span>
                                        <a href="tel:+91XXXXXXXXXX" class="text-decoration-none">+91 XXXXXXXXXX</a>
                                    </div>

                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-dark">Email</span>
                                        <a href="mailto:anand@example.com" class="text-decoration-none">anand@example.com</a>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 d-flex justify-content-end">
                                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">
                                    Go Back
                                </a>
                            </div>
                        </div>
                    </div>

                    <p class="text-muted small text-center mt-3 mb-0">
                        If you believe this is a mistake, please reach out to the admin for role update.
                    </p>
                </div>
            </div>
        </div>
    @endif

@endsection
