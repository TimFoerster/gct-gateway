import Alpine from 'alpinejs'
import * as echarts from 'echarts';
import persist from '@alpinejs/persist'

Alpine.plugin(persist)
window.Alpine = Alpine

let mode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ?
    'dark' : 'light';

var c = document.getElementById('echart')!;
var myChart = echarts.init(c, mode);

document.addEventListener('alpine:init', () => {
    Alpine.data('globalFilters', () => ({
        simulations: [],
        devices: {},
        init() {
             fetch("/chart/simulations")
                 .then(res => res.json())
                 .then(data => {
                     this.simulations = data;
                     this.simulations.forEach((el) => {
                         el.devices.forEach((d) => {
                             this.devices[d.id] = d;
                         });
                     });
                 });
        },
        scenarios: [],
        toggleScenario(id) {
            if (this.activeScenario(id))
                this.scenarios = this.scenarios.filter(o => o != id);
            else
                this.scenarios = [...this.scenarios, id];
        },
        activeScenario(id) {
            return this.scenarios.indexOf(id) > -1;
        },

        seeds: [],
        toggleSeed(id) {
            if (this.activeScenario(id))
                this.seeds = this.seeds.filter(o => o != id);
            else
                this.seeds = [...this.seeds, id];
        },
        activeSeed(id) {
            return this.seeds.indexOf(id) > -1;
        },

        algorithms: [],
        toggleAlgorithm(id) {
            if (this.activeAlgorithm(id))
                this.algorithms = this.algorithms.filter(o => o != id);
            else
                this.algorithms = [...this.algorithms, id];
        },
        activeAlgorithm(id) {
            return this.algorithms.indexOf(id) > -1;
        },

        app_intervals: [],
        toggleAppInterval(id) {
            if (this.activeAppInterval(id))
                this.app_intervals = this.app_intervals.filter(o => o != id);
            else
                this.app_intervals = [...this.app_intervals, id];
        },
        activeAppInterval(id) {
            return this.app_intervals.indexOf(id) > -1;
        },

        showSimulation(simulation: any) {
            return (this.scenarios.length == 0 || this.activeScenario(simulation.scenario))
                && (this.seeds.length == 0 || this.activeSeed(simulation.seed))
                && (this.algorithms.length == 0 || this.activeAlgorithm(simulation.algorithm))
                && (this.app_intervals.length == 0 || this.activeAppInterval(simulation.app_interval));
        },

        activeSimulations: [],
        toggleSimulation(simulation) {
            if (this.simulationActive(simulation))
                this.activeSimulations = this.activeSimulations.filter(o => o != simulation.id);
            else
                this.activeSimulations = [...this.activeSimulations, simulation.id];
        },
        simulationActive(simulation) {
            return this.showSimulation(simulation) &&
                this.activeSimulations.indexOf(simulation.id) > -1;
        },
        span(amount) {
            return 'grid-row: span ' + amount;
        },

        activeDevices: [],
        toggleDevice(device) {
            if (this.activeDevice(device))
                this.activeDevices = this.activeDevices.filter(o => o != device.id);
            else
                this.activeDevices = [...this.activeDevices, device.id];

            this.onDeviceChange();
        },
        activeDevice(device) {
            return this.activeDevices.indexOf(device.id) > -1;
        },

        onDeviceChange() {
            fetch("/chart/devices?deviceIds="+this.activeDevices.join(',') )
                .then(res => res.json())
                .then(data => {
                    let series: echarts.EChartOption.Series[] = [];

                    let legends: string[] = [];
                    let selected: object = {};
                    Object.keys(data).forEach((row, index) => {
                        var device = this.devices[row];
                        var serieData = data[row].map((v) => [v.time, v.value, v.length, v.min, v.max, v.variance, v.standard_deviation, v.packages, v.unique_packages]);
                        var name = "S" + device.simulation_id + ": " + (!device.global_name ? 'World' : ([device.global_name, device.global_id, device.local_id].filter(o => o != null).join('-')));
                        legends.push(name, name + " range");
                        selected[name] = true;
                        selected[name + " range"] = false;
                        series.push({
                           name: name,
                            coordinateSystem: 'polar',
                           type: 'scatter',
                           data: serieData,
                           dimensions: chartDimensions,
                           symbolSize: (value, params) => {
                               return value[2] * value[2] * 5;
                           },
                           encode: {
                               tooltip: [0,1,2,3,4,5,6,7,8]
                           }
                        },
                        {
                           type: 'custom',
                           name: name + " range",
                            coordinateSystem: 'polar',
                            renderItem: renderItem,
                           dimensions: chartDimensions,
                           data: serieData,
                           z: 100,
                           symbolSize: 1,
                           encode: {
                               tooltip: [0,1,2,3,4,5,6,7,8]
                           }
                        });
                    });

                    myChart.setOption({
                        legend: {
                            data: legends,
                            selected: selected
                        },
                        series: series,
                    });
                })


        }

    }));
});

Alpine.start();

const chartDimensions = [
    'time', 'mean', 'length', 'min', 'max', 'variance', 'std deviation', '#packages', '#unique packes'
];

let coordDims = ['x', 'y'];

function makeShape(
    baseDimIdx: number,
    base1: number,
    value1: number,
    base2: number,
    value2: number
) {
    var shape: Record<string, number> = {};
    shape[coordDims[baseDimIdx] + '1'] = base1;
    shape[coordDims[1 - baseDimIdx] + '1'] = value1;
    shape[coordDims[baseDimIdx] + '2'] = base2;
    shape[coordDims[1 - baseDimIdx] + '2'] = value2;
    return shape;
}

function renderItem(
    params: echarts.EChartOption.SeriesCustom.RenderItemParams,
    api: echarts.EChartOption.SeriesCustom.RenderItemApi
): echarts.EChartOption.SeriesCustom.RenderItemReturnLine {
    const group:  echarts.EChartOption.SeriesCustom.RenderItemReturnGroup = {
        type: 'group',
        children: []
    };

    let x = api.value!(0);

    let low = api.value!(3);
    let high = api.value!(4);

    var style = api.style!({
        stroke: api.visual!('color') as string,
        fill: undefined,
        width: 1,
        type: 'dashed',
        opacity: .25
    });

    let lowPoint = api.coord!([x, low]);
    let highPoint = api.coord!([x, high]);

    console.log(lowPoint, highPoint);
    group.children!.push({
        type: 'bar',
        coordinateSystem: 'polar',
        transition: ['shape'],
        silent: true,
        shape: makeShape(
            0,
            highPoint[0],
            highPoint[1],
            lowPoint[0],
            lowPoint[1]
        ),
        style: style
    });
    return group;
}

// Draw the chart
myChart.setOption({
    title: {},
    tooltip: {
        trigger: 'axis',
        axisPointer: {
            type: 'cross'
        }
    },
    dataZoom: [
        {
            type: 'slider'
        },
        {
            type: 'inside'
        }
    ],
    polar: {},
    angleAxis: {
        type: 'value',
        startAngle: 0,
        min: 0,
        max: 18446744073709551615,
        axisLabel: {
            formatter: function (value, index) {
                if (value == 0 ) return 0;
                return value.toExponential(2);
            }
        }
    },
    radiusAxis: {
        name: 'Time',
        type: 'value',
        minInterval: 0,
        axisLabel: {
            formatter: function (value) {
                return Math.floor(value/3600).toString().padStart(2, '0') + ":" + (Math.floor(value/60)%60).toString().padStart(2, '0');
            }
        }
    },
    series: [
    ]
});
