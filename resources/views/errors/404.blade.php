@extends('errors::layout')

@section('title', 'Page not found')
@section('code', '404')
@section('eyebrow', 'A little off course')
@section('heading', 'This page isn’t in our catalogue.')
@section('message', 'Let’s help you find the right equipment instead.')

@section('actions')
    <a class="button button-primary" href="{{ url('/catalogue') }}">Explore Equipment <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></a>
    <a class="button button-outline" href="{{ url('/') }}">Back to Home</a>
@endsection
