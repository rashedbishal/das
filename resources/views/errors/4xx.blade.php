@extends('errors::layout')

@section('title', 'Request not completed')
@section('code')
    {{ $exception->getStatusCode() }}
@endsection
@section('eyebrow', 'A little off course')
@section('heading', 'We couldn’t complete that request.')
@section('message', 'The link may be out of date, or something about the request wasn’t quite right. Please try again.')

@section('actions')
    <a class="button button-primary" href="{{ url('/') }}">Back to Home <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></a>
    <a class="button button-outline" href="{{ url('/contact') }}">Contact us</a>
@endsection
