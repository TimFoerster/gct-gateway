@extends('layout', ['scrolling' => true])

@section('body')

    <div class="flex flex-row justify-between w-full overflow-hidden mt-4 gap-2" >

        <div class="panel w-full">
            <div>
                <h3>Calculation {{$calculation->id}}</h3>
                Calculation step: {{$calculation->timestep}} - {{$calculation->status}}
            </div>
            <canvas id="myChart"></canvas>
            <div class="flex flex-col gap-2">
                <div class="flex flex-row gap-2 flex-wrap">
                    <div>
                        <a href="/calculation/compare/{{$calculation->id}}">compare</a>
                    </div>
                    @foreach($sets as $set)
                        <div>
                            <a class="underline" style="text-decoration-color: {{$set->color}}" href="/device/{{$set->device->id}}">{{$set->device->name}}</a><br>
                            avg: {{$set->avg_length}}<br>
                            sum: {{$set->sum_length}}<br>
                            med: {{$set->median_length}}<br>
                            stable: {{count($set->stables) * $calculation->timestep}}s
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
                        label: '{{$set->device->name}}',
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
                    {
                        label: 'Stable {{$set->device->name}} ',
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
                @endforeach

            ]
        }
    });
</script>
@endsection
