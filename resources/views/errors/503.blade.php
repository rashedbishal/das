{{-- Maintenance mode (php artisan down). Deploys pre-render this page into a
     static file (--render="errors::503"), so it must not need the database or
     the session; see errors/layout.blade.php. It reloads itself every 30s, so
     visitors land back on the site as soon as it is up. --}}
@extends('errors::layout')

@section('title', 'Back shortly')
@section('refresh', '30')
@section('code', '503')
@section('eyebrow', 'Scheduled maintenance')
@section('heading', 'We’re making a few improvements.')
@section('message', 'DAS is being updated and will be back in a few minutes. Thank you for your patience.')
@section('note', 'This page refreshes on its own when we’re back.')
