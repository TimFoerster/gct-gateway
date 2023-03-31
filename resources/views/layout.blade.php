<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="stylesheet" href="{{ mix('css/app.css') }}">
        <title>{{ $title ?? 'Simulations' }}</title>
    </head>
    <body class="antialiased">
        <div style="@if(!isset($scrolling)) max-height: 100vh @endif " class="w-full relative flex justify-center min-h-screen sm:items-center">

            <div style="@if(!isset($scrolling)) max-height: 100vh @endif " class="flex flex-col w-full mx-auto p-2">

                <div class="w-full flex flex-row justify-between">

                    @if(!isset($isIndex) || !$isIndex)
                        <div class="">
                            <a href="/">&leftarrow; List</a>
                        </div>
                    @endif

                    @if(isset($isIndex) && !$isIndex && isset($simulation))
                    <div class="">
                        <a href="/simulation/{{$simulation->id}}">{{$simulation->id}} - {{$simulation->scenario}} - {{$simulation->seed}} - {{$simulation->recording}}</a><br>
                        {{$simulation->start}} -> {{$simulation->end}}<br>
                        {{$simulation->status}} - {{$simulation->person_count}}#<br>
                        @if(isset($device))
                            {{$device->type->name}} - {{$device->name}}
                        @endif
                    </div>
                    @endif
                </div>

                @yield('body')

                <div class="flex justify-center mt-4 sm:items-center sm:justify-between">
                    <div class="text-center text-sm text-gray-500 sm:text-left">
                        <div class="flex items-center">

                        </div>
                    </div>

                    <div class="ml-4 text-center text-sm text-gray-500 sm:text-right sm:ml-0">
                        <a href="/">Sim 1.0.0</a> | <a href="/download">build downloads</a>
                    </div>
                </div>
            </div>

        </div>
    </body>
</html>

