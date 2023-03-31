@extends('layout')

@section('body')

    <div class="w-full flex flex-row gap-2">
        <div class="flex">Processes: {{number_format($queueSize)}}</div>
        <div class="flex">Received messages: {{number_format($receivedCount)}}</div>
        <div class="flex">Statistics: {{number_format($statisticCount)}}</div>

        <div class="flex flex-row flex-grow justify-end gap-1">
            <a href="/chart" class="bt-default">Chart</a>
            <form class="" action="/reset" method="POST">
                <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                <input class="bt-default bt-danger" type="submit" value="Reset">
            </form>
            <form class="" action="/cleanup" method="POST">
                <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                <input class="bt-default bt-danger" type="submit" value="Cleanup">
            </form>
        </div>

    </div>

    <div class="mt-8 bg-default overflow-hidden shadow sm:rounded-lg">
        <table id="scenarios">
            <thead>
                <tr>
                    <td>ID</td>
                    <td>Scenario</td>
                    <td>Seed</td>
                    <td>Start</td>
                    <td>End</td>
                    <td>Status</td>
                    <td>Options</td>
                    <td>Persons</td>
                    <td>App Interval</td>
                    <td>Broadcast Interval</td>
                    <td>Algorithm</td>
                    <td>Device</td>
                    <td>Processes#</td>
                    <td>Calculations</td>
                    <td>Status</td>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="13">Loading...</td></tr>
            </tbody>
        </table>
    </div>
@endsection

<link rel="stylesheet" href="{{ mix('css/datatable.css') }}">

<style>
    #scenarios tbody tr td {
        cursor: pointer;
    }
    #scenarios tbody tr:hover {
        text-decoration: underline;
    }
</style>
<script src="{{mix('js/datatable.js')}}"></script>
<script>
    $(document).ready( function () {
        var scenarioTable = $('#scenarios');
        var dt = scenarioTable.DataTable({
            serverSide: true,
            ajax: "simulation/simulations.json",
            scrollCollapse: true,
            paging: false,
            order: [[0, 'desc']],
            columnDefs: [
                {
                    render: function (data, type, row) {
                        var str = "";
                        Object.keys(data).forEach(key => {
                            str += key + ": " + data[key] + "<br>"
                        });
                        return str;
                    },
                    targets: 6,
                },
                { visible: false, targets: [3] },
            ],
            columns: [
                { data: 'sim_id', searchable: false, orderable: true },
                { data: 'scenario', searchable: false, orderable: true },
                { data: 'seed', searchable: false, orderable: true },
                { data: 'sim_start', searchable: false, orderable: true },
                { data: 'sim_end', searchable: false, orderable: true },
                { data: 'sim_status', searchable: false, orderable: true },
                { data: 'simulation_options', searchable: false, orderable: true },
                { data: 'person_count', searchable: false, orderable: true },
                { data: 'app_interval', searchable: false, orderable: true },
                { data: 'broadcast_interval', searchable: false, orderable: true },
                { data: 'algorithm', searchable: false, orderable: true },
                { data: 'device_name', searchable: false, orderable: true },
                { data: 'processes_count', searchable: false, orderable: true },
                { data: 'calc_id', searchable: false, orderable: true },
                { data: 'status', searchable: false, orderable: true },
            ]
        });

        scenarioTable.on('click', 'tbody tr', function() {
            window.location.assign("/simulation/" + dt.row(this).data().sim_id);
        });

        setInterval( function () {
            dt.ajax.reload();
        }, 10000 );
    });
</script>
