@extends('layout', ['scrolling' => true])

@section('body')

    <div class="flex flex-row justify-between w-full overflow-hidden mt-4 gap-2" >

        <div class="panel w-full">
            <div class="flex flex-row gap-2 align-baseline">
                <h3>Compare calculation {{$calculation->id}}</h3>
                <a href="{{$calculation->id}}?scenario={{$scenario}}&seed={{$seed}}&calculation={{$compareCalculation}}&global={{!$withGlobal}}" class="bt-default {{$withGlobal ? 'bt-active' : '' }}">Global</a>
                @foreach($scenarios as $scenarioName => $count)
                    <a href="{{$calculation->id}}?scenario={{$scenarioName == $scenario ? '' : $scenarioName }}&seed={{$seed}}&calculation={{$compareCalculation}}&global={{$withGlobal}}" class="bt-default {{$scenarioName === $scenario ? 'bt-active' : '' }}">{{$scenarioName}} ({{$count}})</a>
                @endforeach
                <a href="{{$calculation->id}}?scenario={{$scenario}}&seed={{$simulation->seed}}&calculation={{$compareCalculation}}&global={{$withGlobal}}" class="bt-default {{$seed ? 'bt-active' : '' }}">Seed ({{$seedCount}})</a>
            </div>
            <div class="mt-2">
                @foreach($calculationOptions as $calculationId => $simulationId)
                    <a href="{{$calculation->id}}?scenario={{$scenario}}&seed={{$seed}}&calculation={{$calculationId}}&global={{$withGlobal}}" class="bt-default {{$compareCalculation == $calculationId ? 'bt-active' : '' }}">{{$scenario}} - Sim {{$simulationId}} - Calc {{$calculationId}}</a>
                @endforeach
            </div>
            <canvas id="myChart"></canvas>
            <div class="flex flex-col gap-2">
                <div class="flex flex-row gap-2 flex-wrap">
                    @foreach($sets as $set)
                        <div>
                            <a class="underline" style="text-decoration-color: {{$set->color}}" href="/device/{{$set->device->id}}">{{$set->simulation->id}} - {{$set->device->name}}</a><br>
                            avg: {{$set->avg_length}}<br>
                            sum: {{$set->sum_length}}<br>
                            med: {{$set->median_length}}<br>
                            @if($set->stables) stable: {{count($set->stables) * $set->calculation->timestep}}s @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

<script src="{{mix('js/chart.js')}}"></script>

<script>
    const ctx = document.getElementById('myChart');
    const myChart = new Chart(ctx, {
        type: 'scatter',
        options: {
            plugins: {
                tooltip: {
                    enabled: false
                },
            },
            animation: false,
            parsing: false,
            normalized: true,
            scales: {
                x: {
                    type: 'linear',
                    position: 'bottom',
                    min: 0,
                    max: {{$simulation->end_time}}
                },
                y: {
                    type: 'linear',
                    position: 'left',
                    min: 0,
                    max: 18446744073709551615
                }
            }
        },
        data: {
            datasets: [
                    @foreach($sets as $set)
                {
                    label: '{{$set->simulation->id}} - {{$set->device->name}}',
                    data: [
                            @foreach($set->stats as $stat)
                        {
                            x: {{$stat->time}},
                            y: {{$stat->value}},
                        },
                        @endforeach
                    ],
                    pointStyle : '{{$set->point_style}}',
                    borderColor: '{{$set->color}}',
                    borderWidth: 0.5,
                    hoverRadius: 0.5,
                    hidden:true
                },
                @if ($set->stables)
                {
                    label: '{{$set->simulation->id}} - Stable {{$set->device->name}} ',
                    data: [
                            @foreach($set->stables as $s)
                        {
                            x: {{$s->time}},
                            y: {{$s->value}},
                        },
                        @endforeach
                    ],
                    pointStyle : 'circle',
                    backgroundColor: '{{$set->color}}',
                    borderWidth: 0.5,
                    hoverRadius: 0.5,
                },
                @endif
                @endforeach

            ]
        }
    });
</script>
@endsection
