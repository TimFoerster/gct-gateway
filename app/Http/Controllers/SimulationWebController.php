<?php

namespace App\Http\Controllers;

use App\Http\Requests\SimulationReceivedRequest;
use App\Http\Requests\SimulationSendRequest;
use App\Http\Requests\SimulationStartRequest;
use App\Jobs\DeleteReceivedMessages;
use App\Jobs\ProcessReceiveArchive;
use App\Jobs\ProcessSendArchive;
use App\Jobs\ResetSimulation;
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

class SimulationWebController extends BaseController {

    public function simulations() {
        $query = Simulation::query()
            ->selectRaw('simulations.id as sim_id')
            ->selectRaw('calculations.id as calc_id')
            ->selectRaw('simulations.start as sim_start')
            ->selectRaw('simulations.end as sim_end')
            ->selectRaw('simulations.status as sim_status')
            ->selectRaw('simulations.*, calculations.*')
            ->join('calculations', 'simulations.id', '=', 'calculations.simulation_id', 'LEFT OUTER');
        return datatables($query)->toJson();
    }

    public function detail($id)
    {
        $simulation = Simulation::findOrFail($id);
        return view('simulation.detail', [
            'simulation' => $simulation,
            'devices' => $simulation->devices,
            'calculations' => $simulation->calculations
        ]);
    }

    public function deleteReceived($id) {
        $simulation = Simulation::findOrFail($id);
        foreach ($simulation->devices as $device) {
            DeleteReceivedMessages::dispatch($device->id)
                ->onQueue('high');
        }

        return redirect('/simulation/'.$id);
    }

    public function reset($simulationId) {
        ResetSimulation::dispatch($simulationId)
            ->onQueue('high');

        return redirect('/simulation/'.$simulationId);
    }

}
