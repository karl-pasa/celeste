@extends('layouts.app')

@section('title', 'Transcript of Records')
@section('subtitle', 'Enter the details for one student, or import a batch')

@section('content')
    @livewire('certificates.transcript-issue')
@endsection
