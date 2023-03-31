@extends('layout')

@section('body')
    <div class="flex grow flex-col gap-2 w-full overflow-hidden mt-4" >
        <div class="panel shrink-0">
            <h3>Calculations</h3>
            <div>
                @forelse ($calculations as $calc)
                    <a class="underline" href="/calculation/{{$calc->id}}">Calculation {{$calc->id}} - {{$calc->status}}</a>
                @empty
                    No calculation started yet
                @endforelse
            </div>
        </div>
        <div class="flex flex-row justify-between gap-2 overflow-hidden">
            <div class="panel grow-2">
                <h3>Log</h3>
                <div class="w-full overflow-y-scroll">
                    @foreach($simulation->log as $e)
                        <pre>{{ nl2br($e)}}</pre>

                    @endforeach
                </div>
            </div>
            <div class="panel grow">
                <div class="flex flex-row gap-2 justify-between">
                    <h3 class="flex">Devices</h3>
                    <div class="flex flex-row gap-2">
                        <form class="flex flex-col" action="{{$simulation->id}}/reset" method="POST">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                            <input class="bt-default bt-danger" type="submit" value="reset">
                        </form>

                        @if(count($calculations) > 0 && $calculations[0]->status === 'completed')
                            <form class="flex flex-col" action="{{$simulation->id}}/received" method="POST">
                                {{ method_field('DELETE') }}
                                <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                <input class="bt-default bt-danger" type="submit" value="delete received messages">
                            </form>
                        @endif
                    </div>

                </div>
                @if($simulation->processes_count > 0)
                    <div class="text-yellow-600">
                        {{$simulation->processes_count}} jobs are being processed
                    </div>
                @endif
                <div style="width: 100%; overflow-y: scroll; padding: 2px;">
                    @foreach($devices as $d)
                        @if($d->global_name == "person")
                            <div class="underline" @if(!$d->group_status != 'completed') class="text-yellow-600" @endif><a href="/device/{{$d->id}}">Person {{$d->global_id}} ({{number_format($d->group_count)}})</a> - {{$d->group_status}}</div>
                        @else
                            <div class="underline" @if(!$d->isGenerated() && $d->received_status != 'completed') class="text-yellow-600" @endif><a href="/device/{{$d->id}}">{{ $d->name}} @if(!$d->isGenerated())({{number_format($d->received_count + $d->send_count)}})@endif</a> - {{$d->isGenerated() ? 'generated' : $d->received_status}}</div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

    </div>
@endsection

@if ($simulation->status === 'started' || $simulation->processes_count > 0)

    <script>

        setInterval(function () {
            location.reload();
        }, 10000);
    </script>

@endif
