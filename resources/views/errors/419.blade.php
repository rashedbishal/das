@extends('errors::layout')

@section('title', 'Page expired')
@section('code', '419')
@section('eyebrow', 'Session expired')
@section('heading', 'This page timed out.')
@section('message', 'For your security, forms expire after a while. Please go back, refresh the page and try again.')

@section('actions')
    <a class="button button-primary" href="{{ url('/') }}">Back to Home <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></a>
@endsection
