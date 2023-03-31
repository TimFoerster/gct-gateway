<?php

namespace App\Http\Controllers;

use App\Jobs\CleanupJob;
use App\Jobs\DeleteReceivedMessages;
use App\Models\Device;
use App\Models\Simulation;
use App\Models\Statistic;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpFoundation\Request;

class ChartController  extends BaseController
{
    public function simulationData() {
        $response = [];
        foreach(Simulation::with('devices')->get() as $simulation) {
            $obj = $simulation->toArray();
            $obj["nested_devices"] = $simulation->nestedDeviceTree();
            $response[] = $obj;
        }
        return $response;
    }

    public function deviceData(Request $request) {

        $deviceIds = $request->get('deviceIds');
        if (!$deviceIds)
            return [];

        $ids = explode(',', $request->get('deviceIds'));
        $statistics = Statistic::query()
            ->whereIn('device_id', $ids)
            ->where('unique_packages', '>', 1)
            ->get();

        $response = [];
        foreach($ids as $id) {
            $response[$id] = [];
        }
        foreach($statistics as $statistic) {
            $response[$statistic->device_id][] = $statistic;
        }

        return $response;
    }

    public function index() {
        return view('chart',
            [
                'scenarios' => Simulation::scenarios(),
                'seeds' => Simulation::seeds(),
                'auis' => Simulation::auis(),
                'algorithms' => Simulation::algorithms(),
                'isIndex' => true
            ]);
    }

    public function radar() {
        return view('radar',
            [
                'scenarios' => Simulation::scenarios(),
                'seeds' => Simulation::seeds(),
                'auis' => Simulation::auis(),
                'algorithms' => Simulation::algorithms(),
                'isIndex' => true
            ]);
    }

    public function series() {
        return view('series',
            [
                'scenarios' => Simulation::scenarios(),
                'seeds' => Simulation::seeds(),
                'auis' => Simulation::auis(),
                'algorithms' => Simulation::algorithms(),
                'isIndex' => true
            ]);
    }

    public function seriesData(Request $request) {

        $deviceIds = $request->get('deviceIds');
        if (!$deviceIds)
            return [];

        $ids = explode(',', $request->get('deviceIds'));
        $statistics = Statistic::query()
            ->whereIn('device_id', $ids)
            ->where('unique_packages', '>', 1)
            ->orderBy('device_id')
            ->orderBy('timestep')
            ->get();

        foreach($ids as $id) {
            $series[$id] = [];
        }

        $prevDeviceId = null;
        $prevTimestep = null;
        $startTime = 0;
        $nextIndex = 0;
        foreach ($statistics as $statistic) {
            if ($prevDeviceId === null || $prevDeviceId != $statistic->device_id) {
                $prevDeviceId = $statistic->device_id;
                $prevTimestep = null;
                $nextIndex = 0;
                $startTime = $statistic->time;
            }

            if ($prevTimestep !== null && $prevTimestep + 1 != $statistic->timestep) {
                if (count($series[$prevDeviceId][$nextIndex]) < 100) {
                    $series[$prevDeviceId][$nextIndex] = [];
                    $nextIndex--;
                }
                $nextIndex++;
                $startTime = $statistic->time;
            }

            $statistic->time -= $startTime;
            $series[$prevDeviceId][$nextIndex][] = $statistic;
            $prevTimestep = $statistic->timestep;
        }

        return $series;
    }
}
