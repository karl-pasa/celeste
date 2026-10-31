@extends('layouts.app')

@section('title', 'All certificates')
@section('subtitle', 'Search, verify integrity, revoke, or reissue any document on file')

@section('content')
    @livewire('certificates.certificate-table')
@endsection