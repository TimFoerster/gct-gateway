@extends('layout')

@section('body')

    <div class="flex flex-col gap-2 mt-2">
        <div class="flex flex-col gap-2" x-data="globalFilters">
            <div class="flex flex-row gap-2">
                <div class="flex flex-col gap-2">
                    <h1>Scenarios</h1>
                    @foreach($scenarios as $name => $count)
                        <button :class="activeScenario('{{$name}}') && 'bt-active'" @click="toggleScenario('{{$name}}')" class="bt-default">{{$name}} ({{$count}})</button>
                    @endforeach
                </div>
                <div class="flex flex-col gap-2">
                    <h1>Seeds</h1>
                    @foreach($seeds as $name => $count)
                        <button :class="activeSeed({{$name}}) && 'bt-active'" @click="toggleSeed({{$name}})"  class="bt-default">{{$name}} ({{$count}})</button>
                    @endforeach
                </div>
                <div class="flex flex-col gap-2">
                    <h1>Algorithm</h1>
                    @foreach($algorithms as $name => $count)
                        <button :class="activeAlgorithm('{{$name}}') && 'bt-active'" @click="toggleAlgorithm('{{$name}}')"  class="bt-default">{{$name}} ({{$count}})</button>
                    @endforeach
                </div>
                <div class="flex flex-col gap-2">
                    <h1>App Update Interval</h1>
                    @foreach($auis as $name => $count)
                        <button :class="activeAppInterval({{$name}}) && 'bt-active'" @click="toggleAppInterval({{$name}})"  class="bt-default">{{$name}} ({{$count}})</button>
                    @endforeach
                </div>
            </div>
            <div class="grid gap-2" style="grid-template-columns: max-content max-content max-content auto;">
            <template x-for="simulation in simulations">
                <div style="display: contents">
                    <div :style="span(simulation.nested_devices.globals.length)" >
                        <div class="cursor-pointer" x-show="showSimulation(simulation)" @click="toggleSimulation(simulation)">
                            <div x-text="simulation.id"></div>
                            <div x-text="simulation.scenario"></div>
                            <div x-text="simulation.seed"></div>
                            <div x-text="simulation.algorithm"></div>
                            <div x-text="simulation.app_interval"></div>
                        </div>
                    </div>
                    <div :style="span(simulation.nested_devices.globals.length)" >
                        <button x-show="simulationActive(simulation)" :class="activeDevice(simulation.nested_devices.world) && 'bt-active'" @click="toggleDevice(simulation.nested_devices.world)" class="bt-default">World</button>
                    </div>
                    <template x-for="global in simulation.nested_devices.globals">
                        <div style="display: contents">
                            <div >
                                <button x-show="simulationActive(simulation)" :class="activeDevice(global.device) && 'bt-active'" @click="toggleDevice(global.device)" class="bt-default"><span x-text="global.device.global_name"></span> <span x-text="global.device.global_id"></span></button>
                            </div>
                            <div>
                                <div class="flex flex-row flex-wrap gap-1">
                                    <template x-for="device in global.locals">
                                        <button x-show="simulationActive(simulation)" :class="activeDevice(device) && 'bt-active'" @click="toggleDevice(device)" class="bt-default"><span x-text="device.global_name"></span> <span x-text="device.global_id"></span> <span x-text="device.local_id"></span></button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
        </div>
        <div id="echart" style="height:1000px;"></div>

    </div>

    <script src="{{mix('js/echarts.js')}}"></script>
@endsection
