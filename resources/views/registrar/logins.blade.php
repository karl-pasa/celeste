@extends('layouts.app')

@section('title', 'Student accounts')
@section('subtitle', 'Create student accounts and reset their passwords')

@section('content')
    @livewire('registrar.student-logins')
@endsection