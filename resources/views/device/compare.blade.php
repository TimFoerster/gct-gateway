@extends('layout', ['scrolling' => true])


@section('body')

    <div class="flex flex-row justify-between w-full overflow-hidden mt-4 gap-2" >

        <div class="panel w-full" >
            <div class="text-2xl w-full text-center">Points</div>
            <canvas id="scatter"></canvas>
            <div class="flex flex-row gap-2">
                @foreach($datasets as $set)
                    <div>
                        <a class="inline-block underline" style="text-decoration-color: {{$set->color}};" href="/device/{{$set->device->id}}">Sim {{$set->simulation->id}}, Calc {{$set->calculation->id}}</a>
                        <div>
                            broadcast interval: {{$set->simulation->broadcast_interval}}s<br>
                            app interval: {{$set->simulation->app_interval}}s<br>
                            calc step: {{$set->calculation->timestep}}<br>
                            avg: {{$set->avg_length}}<br>
                            sum: {{$set->sum_length}}<br>
                            med: {{$set->median_length}}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="panel w-full" >
            <div class="text-2xl w-full text-center">Diff</div>
            <canvas id="changes"></canvas>
            <div class="flex flex-row gap-2">
                @foreach($datasets as $set)
                    <div>
                        <a class="inline-block underline" style="text-decoration-color: {{$set->color}};" href="/device/{{$set->device->id}}">Sim {{$set->simulation->id}}, Calc {{$set->calculation->id}}</a>
                        <div>
                            broadcast interval: {{$set->simulation->broadcast_interval}}s<br>
                            app interval: {{$set->simulation->app_interval}}s<br>
                            calc step: {{$set->calculation->timestep}}<br>
                            avg: {{$set->avg_length}}<br>
                            sum: {{$set->sum_length}}<br>
                            med: {{$set->median_length}}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="panel w-full" >
            <div class="text-2xl w-full text-center">Stable</div>
            <canvas id="stables"></canvas>
            <div class="flex flex-row gap-2">
                @foreach($datasets as $set)
                    <div>
                        <a class="inline-block underline" style="text-decoration-color: {{$set->color}};" href="/device/{{$set->device->id}}">Sim {{$set->simulation->id}}, Calc {{$set->calculation->id}}</a>
                        <div>
                            #{{$set->stables->count()}}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>


<link rel="stylesheet" href="{{ mix('css/datatable.css') }}">

<script src="{{mix('js/chart.js')}}"></script>

<script src="{{mix('js/datatable.js')}}"></script>
<script>

    const ctx = document.getElementById('scatter');
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
                    max: {{$datasets[0]->simulation->end_time}}
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
                @foreach($datasets as $set)
                {
                    label: 'Sim {{$set->simulation->id}}, Calc {{$set->calculation->id}}, {{$set->calculation->timestep}}',
                    data: [
                        @foreach($set->statistics as $stat)
                        {
                            x: {{$stat->time}},
                            y: {{$stat->value}},
                        },
                        @endforeach
                    ],
                    backgroundColor: '{{$set->color}}'
                },
                @endforeach
            ]
        }
    });

    const ctx2 = document.getElementById('changes');
    const changesChart = new Chart(ctx2, {
        type: 'line',
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
                    max: {{$datasets[0]->simulation->end_time}}
                },
                y: {
                    type: 'logarithmic',
                    position: 'left',
                    min: 0,
                    max: 18446744073709551615
                }
            }
        },
        data: {
            datasets: [
                    @foreach($datasets as $set)
                {
                    label: 'Sim {{$set->simulation->id}}, Calc {{$set->calculation->id}}, {{$set->calculation->timestep}}',
                    data: [
                        @foreach($set->statistics as $index => $stat)
                        {
                            x: {{$stat->time}},
                            y: {{$index > 0 ? $stat->diff($set->statistics[$index -1]) : $stat->value}},
                        },
                        @endforeach
                    ],
                    backgroundColor: '{{$set->color}}'
                },
                @endforeach
            ]
        }
    });

    const ctx3 = document.getElementById('stables');
    const stablesChart = new Chart(ctx3, {
        type: 'bubble',
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
                    max: {{$datasets[0]->simulation->end_time}}
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
                    @foreach($datasets as $set)
                {
                    label: 'Sim {{$set->simulation->id}}, Calc {{$set->calculation->id}}, {{$set->calculation->timestep}}',
                    data: [
                            @foreach($set->stables as $index => $stat)
                        {
                            x: {{$stat->time}},
                            y: {{$stat->value}},
                            r: {{$stat->unique_packages}}
                        },
                        @endforeach
                    ],
                    backgroundColor: '{{$set->color}}'
                },
                @endforeach
            ]
        }
    });
</script>
@endsection
