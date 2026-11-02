@extends('layouts.app')

@section('title', 'Student records')
@section('subtitle', 'Add, edit, import and remove student records')

@section('content')
    @livewire('registrar.student-records')
@endsection