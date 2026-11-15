<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png" sizes="180x180">
        <link rel="manifest" href="/site.webmanifest">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts'])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>

        {{-- Raw head script, e.g. Google Tag Manager, set from Admin -> Settings -> Tracking. Only the --}}
        {{-- Mother Admin can edit it, and ShareTrackingScripts leaves it out of admin pages. --}}
        {!! $headScripts ?? '' !!}
    </head>
    <body class="font-sans antialiased">
        {{-- Raw body script, e.g. the GTM <noscript> fallback. Same trust boundary as $headScripts. --}}
        {!! $bodyScripts ?? '' !!}
        <x-inertia::app />
    </body>
</html>
