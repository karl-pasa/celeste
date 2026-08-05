@extends('layouts.app')

@section('title', 'Batch generation')
@section('subtitle', 'Issue the same document to a whole cohort in one run')

@section('content')
    @livewire('certificates.generate-batch')
@endsection
