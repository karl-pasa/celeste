@extends('layouts.app')

@section('title', 'Verification analytics')
@section('subtitle', 'Who is checking documents, which ones, and what the pattern suggests')

@section('content')
    @livewire('analytics.analytics-dashboard')
@endsection
