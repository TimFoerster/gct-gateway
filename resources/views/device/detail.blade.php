@extends('layout', ['scrolling' => true])

@section('body')

    <div class="flex flex-row justify-between w-full overflow-hidden mt-4 gap-2" >

        <div class="panel w-full">
            <canvas id="myChart"></canvas>
            <div class="flex flex-col gap-2">
                @foreach($calculations as $calc)
                    <div class="flex flex-row gap-2 flex-wrap">
                        <div>
                            <a class="underline" href="/compare/device/{{$device->id}}">compare</a>
                        </div>
                        <div>
                            <a class="underline" href="/calculation/{{$calc['id']}}">Calculation {{$calc['id']}}</a><br>
                            Calculation step: {{$calc['timestep']}}
                        </div>
                        @if($calc['parent'])
                            <div>
                                <a class="underline" style="text-decoration-color: {{$calc['parent']['color']}}" href="/device/{{$calc['parent']['device']->id}}">{{$calc['parent']['device']->name}}</a><br>
                                avg: {{$calc['parent']['avg_length']}}<br>
                                sum: {{$calc['parent']['sum_length']}}<br>
                                med: {{$calc['parent']['median_length']}}
                            </div>
                        @endif
                        <div>
                            <div>
                                <b style="color: {{$calc['color']}}">{{$device->name}}</b><br>
                                avg: {{$calc['avg_length']}}<br>
                                sum: {{$calc['sum_length']}}<br>
                                med: {{$calc['median_length']}}<br>
                                stable: {{$calc['stable_count'] * $calc['timestep']}}s
                            </div>
                        </div>
                        @foreach($calc['children'] as $child)
                        <div>
                            <a class="underline" style="text-decoration-color: {{$child['color']}}" href="/device/{{$child['device']->id}}">{{$child['device']->name}}</a><br>
                            avg: {{$child['avg_length']}}<br>
                            sum: {{$child['sum_length']}}<br>
                            med: {{$child['median_length']}}<br>
                            stable: {{$child['stable_count'] * $calc['timestep']}}s

                        </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
        @if(!$device->isGenerated())
            <div class="panel w-full">
                <table id="receivedMessages">
                    <thead>
                    <tr>
                        <td>#</td>
                        <td>Time</td>
                        <td>UUID</td>
                        <td>Value</td>
                        <td>Distance</td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr><td colspan="7">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>


<link rel="stylesheet" href="{{ mix('css/datatable.css') }}">

<script src="{{mix('js/chart.js')}}"></script>

<script src="{{mix('js/datatable.js')}}"></script>
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
                    @if ($device->isGenerated() && count($calculations) > 0)
                    min: {{$calculations[0]["stats"][0]->time}},
                    max: {{$calculations[0]["stats"][count($calculations[0]["stats"]) - 1]->time}},
                    @else
                    min: {{$received->first()->time ?? 0}},
                    max: {{$received->last()->time ?? $simulation->end_time}}
                    @endif
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
                @if(count($received) > 0)
                {
                    label: 'Received messages',
                    data: [
                        @foreach($received as $stat)
                        {
                            x: {{$stat->time}},
                            y: {{$stat->value}},
                        },
                        @endforeach
                    ]
                },
                @endif
                @foreach($calculations as $calc)
                {{--
                @if($calc['parent'])
                {
                    label: 'Calculation {{$calc['id']}} - {{$calc['parent']['device']->name}}',
                    data: [
                        @foreach($calc['parent']['stats'] as $stat)
                        {
                            x: {{$stat->time}},
                            y: {{$stat->value}},
                        },
                        @endforeach
                    ],
                    pointStyle : '{{$calc['parent']['point_style']}}',
                    borderColor: '{{$calc['parent']['color']}}'
                },
                @endif
                --}}
                {
                    label: 'Calculation {{$calc['id']}} - {{$device->name}}',
                    data: [
                        @foreach($calc['stats'] as $stat)
                            {
                                x: {{$stat->time}},
                                y: {{$stat->value}},
                            },
                        @endforeach
                    ],
                    pointStyle : '{{$calc['point_style']}}',
                    borderColor: '{{$calc['color']}}'
                },
                @foreach($calc["children"] as $child)
                    {
                        label: 'Calculation {{$calc['id']}} - {{$child['device']->name}}',
                        data: [
                                @foreach($child['stats'] as $stat)
                            {
                                x: {{$stat->time}},
                                y: {{$stat->value}},
                            },
                            @endforeach
                        ],
                pointStyle : '{{$child['point_style']}}',
                borderColor: '{{$child['color']}}',
                borderWidth: 0.5,
                hoverRadius: 0.5
                    },

                @endforeach
            @endforeach
            ]
        }
    });

    @if (!$device->isGenerated())
    $(document).ready( function () {
        $('#receivedMessages').DataTable({
            "serverSide": true,
            "ajax": "{{$device->id}}/received.json",
            columns: [
                { data: 'package_id', searchable: false, orderable: true },
                { data: 'time', searchable: false, orderable: true },
                { data: 'uuid', searchable: false, orderable: true },
                { data: 'value', searchable: false, orderable: true },
                { data: 'distance', searchable: false, orderable: true }
            ]
        });
    });
    @endif
</script>
@endsection
