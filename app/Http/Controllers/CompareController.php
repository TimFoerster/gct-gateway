<?php

namespace App\Http\Controllers;

use App\Http\Requests\SimulationReceivedRequest;
use App\Http\Requests\SimulationSendRequest;
use App\Http\Requests\SimulationStartRequest;
use App\Jobs\ProcessReceiveArchive;
use App\Jobs\ProcessSendArclhive;
use App\Models\Calculation;
use App\Models\Device;
use App\Models\ReceivedMessage;
use App\Models\SendMessage;
use App\Models\Simulation;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Queue;
use stdClass;

class CompareController extends BaseController
{

    public function compareDevice($deviceId)
    {

        $device = Device::findOrFail($deviceId);

        $simulation = $device->simulation;
        $calculations = Calculation::query()
            ->scenario($simulation->scenario)
            ->seed($simulation->seed)
            ->hasCalculations($device->global_name, $device->global_id, $device->local_id)
            ->get();

        $datasets = [];

        foreach ($calculations as $calculation) {
            $otherDevice = $calculation->devices()->comparableDevices($device)->first();
            $set = new stdClass();
            $set->calculation = $calculation;
            $set->device = $otherDevice;
            $set->statistics = $otherDevice->statistics;
            $set->stables = $otherDevice->statistics()->stable()->get();
            $set->simulation = $calculation->simulation;
            $set->color = "rgb(" . join(",", [random_int(0, 255), random_int(0, 255), random_int(0, 255)]) . ")";
            $set->avg_length = $otherDevice->pivot->avg_length;
            $set->sum_length = $otherDevice->pivot->sum_length;
            $set->median_length = $otherDevice->pivot->median_length;
            $datasets[] = $set;
        }

        return view('device.compare', [
            'device' => $device,
            'datasets' => $datasets
        ]);
    }

}
