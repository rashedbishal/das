@extends('errors::layout')

@section('title', 'Sign in required')
@section('code', '401')
@section('eyebrow', 'Members only')
@section('heading', 'Please sign in to continue.')
@section('message', 'This page is available once you are signed in to your DAS account.')

@section('actions')
    <a class="button button-primary" href="{{ url('/login') }}">Sign in <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></a>
    <a class="button button-outline" href="{{ url('/') }}">Back to Home</a>
@endsection
