@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h4 class="fw-bold mb-0 text-danger"><i class="fas fa-key me-2"></i>License Management</h4>
                </div>

                <div class="card-body p-4 text-center">
                    @if(session('success'))
                        <div class="alert alert-success mb-4">
                            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-danger mb-4">
                            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                        </div>
                    @endif

                    <div class="mb-4">
                        <i class="fas fa-server fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">Machine Binding Status</h5>
                    </div>

                    @if($isBound)
                        <div class="alert alert-success d-inline-block px-5">
                            <h3 class="alert-heading fw-bold"><i class="fas fa-lock me-2"></i>SECURE & BOUND</h3>
                            <p class="mb-0">This machine is licensed and bound.</p>
                        </div>
                        <div class="mt-4 d-flex justify-content-center">
                            <form action="{{ route('admin.license.rebind') }}" method="POST" class="mx-2" onsubmit="return confirm('Are you sure you want to re-bind? This will update the hardware fingerprint.');">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger">
                                    <i class="fas fa-sync-alt me-2"></i>Re-bind Machine
                                </button>
                            </form>

                            <form action="{{ route('admin.license.unbind') }}" method="POST" class="mx-2" onsubmit="return confirm('WARNING: You are about to unbind this machine. \n\nThis will remove the license lock. You can move the database to a new server and Bind it there.\n\nAre you sure?');">
                                @csrf
                                <button type="submit" class="btn btn-warning text-dark">
                                    <i class="fas fa-unlink me-2"></i>Unbind (Safe to Move)
                                </button>
                            </form>
                        </div>
                    @elseif($hardwareMismatch)
                        <div class="alert alert-warning d-inline-block px-5">
                            <h3 class="alert-heading fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>HARDWARE MISMATCH</h3>
                            <p class="mb-0">The license does not match this machine's fingerprint.</p>
                        </div>
                        <div class="mt-4">
                            <form action="{{ route('admin.license.rebind') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-warning text-dark fw-bold px-4 py-2">
                                    <i class="fas fa-sync me-2"></i>Update Fingerprint (Re-bind)
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="alert alert-secondary d-inline-block px-5">
                            <h3 class="alert-heading fw-bold"><i class="fas fa-unlock me-2"></i>UNBOUND</h3>
                            <p class="mb-0">This system is not yet bound to this hardware.</p>
                        </div>
                        <div class="mt-4">
                            <form action="{{ route('admin.license.bind') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-danger px-5 py-3 fw-bold shadow">
                                    <i class="fas fa-fingerprint me-2"></i>BIND THIS MACHINE
                                </button>
                            </form>
                        </div>
                    @endif
                    
                    <hr class="my-4">
                    <p class="text-muted small">
                        <i class="fas fa-info-circle me-1"></i> 
                        Binding this machine will generate a unique hardware lock and secure the database.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
