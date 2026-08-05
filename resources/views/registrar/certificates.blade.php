@extends('layouts.app')

@section('title', 'All certificates')
@section('subtitle', 'Search, verify integrity, revoke, or reissue any document on file')

@section('actions')
    <a href="{{ route('registrar.certificates.generate') }}" class="btn btn-sm btn-psu">
        <i class="bi bi-file-earmark-plus"></i> Generate
    </a>
@endsection

@section('content')
    @livewire('certificates.certificate-table')
@endsection
