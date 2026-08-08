@extends('layouts.app')

@section('title', 'Account settings')
@section('subtitle', 'Your details, password, and sign-in history')

@section('content')
    @livewire('account.profile-settings')
@endsection