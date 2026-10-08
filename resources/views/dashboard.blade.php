@extends('layouts.master')

@section('content')
    <div class="card border-0 shadow-sm mx-auto mt-4" style="max-width: 42rem;">
        <div class="card-body p-4 p-md-5">
            <p class="text-uppercase text-muted small fw-semibold mb-2">Dashboard</p>
            <h1 class="h3 fw-bold mb-2">Selamat datang, {{ $user->name }}</h1>
            <p class="text-muted mb-4">{{ $user->email }}</p>

            <div class="alert alert-success mb-0" role="status">
                Anda berhasil login melalui Keycloak dan dapat mengakses halaman ini.
            </div>
        </div>
    </div>
@endsection
