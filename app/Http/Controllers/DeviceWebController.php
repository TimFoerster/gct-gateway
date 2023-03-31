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
use App\Support\PointStyleHelper;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Queue;

class DeviceWebController extends BaseController {

    use PointStyleHelper;

    public function detail($deviceId) {
        /** @var Device $device */
        $device = Device::with('simulation', 'calculations')->findOrFail($deviceId);
        $simulation = $device->simulation;
        $calculations = [];
        //$parent = $device->parent();
        $childs = $device->children();
        $parent = null;
        //$childs = [];

        $receivedQuery = DB::table('received_messages')
            ->where('device_id', $deviceId)
            ->select(['time', 'value']);

        if ($device->received_count > 50000) {
            $receivedQuery->whereRaw('package_id MOD ' . floor($device->received_count / 50000) . ' = 0');
        }

        $received = $receivedQuery->get();

        foreach ($device->calculations as $calculation) {

            if ($parent != null) {
                $parentDevice = $calculation->devices()->where('id', $parent->id)->first();
                $parentSet = [
                    'device' => $parentDevice,
                    'stats' => $parentDevice->statistics()->where('device_id', $parentDevice->id)->get(),
                    'color' => "rgb(" . join(",", [random_int(0, 255), random_int(0, 255), random_int(0, 255)]) . ")",
                    'avg_length' => $parentDevice->pivot->avg_length,
                    'sum_length' => $parentDevice->pivot->sum_length,
                    'median_length' => $parentDevice->pivot->median_length,
                    'point_style' => $this->getPointStyle($simulation, $parentDevice)
                ];
            }
            $children = [];
            foreach($childs as $od) {
                $childDevice = $calculation->devices()->where('id', $od->id)->first();
                $stats = $childDevice->statistics()->where('device_id', $childDevice->id)->get();
                $children[] = [
                    'device' => $childDevice,
                    'stats' => $stats,
                    'stable_count' => $stats->filter(fn($val) => $val->isStable())->count(),
                    'color' => "rgb(" . join(",", [random_int(0, 255), random_int(0, 255), random_int(0, 255)]) . ")",
                    'avg_length' => $childDevice->pivot->avg_length,
                    'sum_length' => $childDevice->pivot->sum_length,
                    'median_length' => $childDevice->pivot->median_length,
                    'point_style' => $this->getPointStyle($simulation, $childDevice)
                ];
            }
            $stats = $calculation->statistics()->where('device_id', $deviceId)->get();
            $calculations[] = [
                'id' => $calculation->id,
                'stats' => $stats,
                'timestep' => $calculation->timestep,
                'avg_length' => $calculation->pivot->avg_length,
                'sum_length' => $calculation->pivot->sum_length,
                'median_length' => $calculation->pivot->median_length,
                'stable_count' => $stats->filter(fn($val) => $val->isStable())->count(),
                'children' => $children,
                'color' => "rgb(" . join(",", [random_int(0, 255), random_int(0, 255), random_int(0, 255)]) . ")",
                'point_style' => $this->getPointStyle($simulation, $device),
                'parent' => $parentSet?? null
            ];
        }

        return view('device.detail', [
            'simulation' => $simulation,
            'device' => $device,
            'calculations' => $calculations,
            'received' => $received
        ]);
    }

    public function receivedMessages($deviceId) {
        // $query = Device::findOrFail($deviceId)->receivedMessages();
        return datatables(ReceivedMessage::where('device_id', $deviceId))->toJson();
    }

    public function sendMessages( $deviceId) {
        $query = Device::findOrFail($deviceId)->sendMessages();
        return datatables($query)->toJson();
    }
}
