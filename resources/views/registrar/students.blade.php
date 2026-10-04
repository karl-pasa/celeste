@extends('layouts.app')

@section('title', 'Student records')
@section('subtitle', 'Add, update and manage the records')

@section('content')
    {{-- The page is a Livewire component now. This file stays a thin wrapper
         so the route, the menu entry and the layout are all untouched. --}}
    @livewire('registrar.student-records')
@endsection
