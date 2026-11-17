@extends('errors::layout')

@section('title', 'Access restricted')
@section('code', '403')
@section('eyebrow', 'Access restricted')
@section('heading', 'This page isn’t available to you.')
@section('message', 'Your account doesn’t have permission to view it. If you think it should, please get in touch.')

@section('actions')
    <a class="button button-primary" href="{{ url('/') }}">Back to Home <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></a>
    <a class="button button-outline" href="{{ url('/contact') }}">Contact us</a>
@endsection
