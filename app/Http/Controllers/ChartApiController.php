<?php

namespace App\Http\Controllers;

use App\Http\Requests\SimulationReceivedRequest;
use App\Http\Requests\SimulationSendRequest;
use App\Http\Requests\SimulationStartRequest;
use App\Jobs\ProcessReceiveArchive;
use App\Jobs\ProcessSendArchive;
use App\Models\Device;
use App\Models\ReceivedMessage;
use App\Models\SendMessage;
use App\Models\Simulation;
use App\Models\Statistic;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChartApiController extends BaseController
{

    protected function getData($data) {
        $result = [];
        foreach ($data as $v => $count) {
            $result[] = ['name' => $v, 'count' => $count];
        }
        return $result;
    }

    public function scenarios() {
        return $this->getData(Simulation::scenarios());
    }
    public function seeds() {
        return $this->getData(Simulation::seeds());
    }
    public function appUpdates() {
        return $this->getData(Simulation::auis());
    }
    public function algorithms() {
        return $this->getData(Simulation::algorithms());
    }

    public function simulations() {
        return Simulation::all();
    }


    public function deviceGroups(Request $request) {
        $simulationIds = $request->get('simulationIds');
        if (!$simulationIds)
            return [];

        $ids = explode(',', $simulationIds);

        $devices = Device::query()
            ->select(['id', 'simulation_id', 'global_name', 'global_id', 'local_id'])
            ->where('global_name', '!=', 'person')
            ->whereIn('simulation_id', $ids)
            ->orderBy('global_id')
            ->orderBy('local_id')
            ->get();

        $groups = ['world' => null, 'global' => []];

        /** @var Device $device */
        foreach ($devices as $device) {
            if ($device->isWorld()) {
                $groups['world'] = $device;
                continue;
            }

            if (!isset($groups['global'][$device->global_name]))
                $groups['global'][$device->global_name] = [];

            if ($device->isGlobal()) {
                if (!isset($groups['global'][$device->global_name][$device->global_id]))
                    $groups['global'][$device->global_name][$device->global_id] = ['device' => $device, 'local' => []];
                else
                    $groups['global'][$device->global_name][$device->global_id]['device'] = $device;
                continue;
            }

            // only locals left
            if (!isset($groups['global'][$device->global_name][$device->global_id])) {
                $groups['global'][$device->global_name][$device->global_id] = ['device' => null, 'local' => [$device->local_id => $device]];
                continue;
            }

            $groups['global'][$device->global_name][$device->global_id]['local'][$device->local_id] = $device;
        }

        return $groups;

    }

    public function dataSeries(Request $request) {

        $simulationIds = $request->get('simulationIds');
        if (!$simulationIds)
            return [];
        $sIds = explode(',', $simulationIds);

        $deviceUuids = $request->get('deviceUuids');
        if (!$deviceUuids)
            return [];
        $dUuids = explode(',', $deviceUuids);

        $devices = Device::query()
            ->whereIn('simulation_id', $sIds)
            ->where(function($subquery) use ($dUuids) {
                foreach ($dUuids as $s) {
                    $ss = explode('-', $s);
                    $subquery->orWhere(function($subsubquery) use ($ss) {
                        $subsubquery->where('global_name', $ss[0]);
                        if (isset($ss[1]))
                            $subsubquery->where('global_id', $ss[1]);
                        $subsubquery->whereNull('local_id');
                    });
                }
            })
            ->get();

        $statistics = Statistic::query()
            ->whereIn('device_id', $devices->pluck('id'))
            ->where('unique_packages', '>', 1)
            ->orderBy('device_id')
            ->orderBy('timestep')
            ->get();

        $series = [];
        $skip = $request->get('skip');
        $drop = $request->get('drop');

        foreach($devices as $device) {
            $series[$device->id] = ['device' => $device, 'series' => []];
        }

        $prevDeviceId = null;
        $prevTimestep = null;
        $startTime = 0;
        $nextIndex = 0;
        foreach ($statistics as $statistic) {

            if ($prevDeviceId != $statistic->device_id) {
                if ($prevDeviceId > -1) {
                    self::calculateOn($series[$prevDeviceId]['series'][$nextIndex]);
                }
                $prevDeviceId = $statistic->device_id;
                $prevTimestep = null;
                $nextIndex = 0;
                $startTime = $statistic->time;
            }

            if ($prevTimestep !== null && $prevTimestep + 1 != $statistic->timestep) {
                if (count($series[$prevDeviceId]['series'][$nextIndex]['points']) < 100) {
                    $series[$prevDeviceId]['series'][$nextIndex] = ['points' => []];
                    $nextIndex--;

                } else {
                    self::calculateOn($series[$prevDeviceId]['series'][$nextIndex]);
                }
                $nextIndex++;
                $startTime = $statistic->time;
            }

            $statistic->time -= $startTime;
            $series[$prevDeviceId]['series'][$nextIndex]['points'][] = $statistic;
            $prevTimestep = $statistic->timestep;
        }

        self::calculateOn($series[$prevDeviceId]['series'][$nextIndex]);

        return $series;
    }

    static private function calculateOn(&$where) {
        $where['median'] = self::calculateMedian($where['points']);
        $where['average'] = self::calculateAverage($where['points']);
        $where['points'] = array_slice($where['points'], 5, count($where['points']) - 11);
    }

    static private function calculateMedian($points) {
        $col = new Collection($points);
        return $col->median('standard_deviation');
    }

    static private function calculateAverage($points) {
        $col = new Collection($points);
        return $col->average('standard_deviation');
    }
}
