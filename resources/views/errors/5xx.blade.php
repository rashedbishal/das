@extends('errors::layout')

@section('title', 'Something went wrong')
@section('code')
    {{ $exception->getStatusCode() }}
@endsection
@section('eyebrow', 'Unexpected error')
@section('heading', 'Something went wrong on our side.')
@section('message', 'Please try again in a moment. If it keeps happening, let us know and we’ll help.')

@section('actions')
    <a class="button button-primary" href="{{ url('/') }}">Back to Home <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></a>
    <a class="button button-outline" href="{{ url('/contact') }}">Contact us</a>
@endsection
