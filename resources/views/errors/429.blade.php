@extends('errors::layout')

@section('title', 'Too many requests')
@section('code', '429')
@section('eyebrow', 'Just a moment')
@section('heading', 'That was a lot of requests at once.')
@section('message', 'Please wait a minute, then try again.')

@section('actions')
    <a class="button button-primary" href="{{ url('/') }}">Back to Home <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></a>
@endsection
