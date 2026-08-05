@extends('layouts.app')

@section('title', 'Generate a document')
@section('subtitle', 'Single issuance · the QR and fingerprint are applied automatically')

@section('content')
    @livewire('certificates.generate-single')
@endsection
