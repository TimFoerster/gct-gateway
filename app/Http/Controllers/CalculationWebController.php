<?php

namespace App\Http\Controllers;

use App\Http\Requests\SimulationReceivedRequest;
use App\Http\Requests\SimulationSendRequest;
use App\Http\Requests\SimulationStartRequest;
use App\Jobs\ProcessReceiveArchive;
use App\Jobs\ProcessSendArchive;
use App\Models\Calculation;
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
use stdClass;

class CalculationWebController extends BaseController {

    use PointStyleHelper;

    public function detail($calculationId) {
        /** @var Calculation $calculation */
        $calculation = Calculation::with('simulation')->findOrFail($calculationId);
        $simulation = $calculation->simulation;

        $devices = [];
        foreach ($calculation->devices()->notLocal()->get() as $device) {
            $devices[] = $this->makeSet($device, $simulation, $calculation);
        }

        return view('calculation.detail', [
            'simulation' => $simulation,
            'calculation' => $calculation,
            'sets' => $devices
        ]);
    }

    protected function makeSet(Device $device, Simulation $simulation, Calculation $calculation, bool $withStable = true) {
        $obj = new stdClass();
        $obj->simulation = $simulation;
        $obj->calculation = $calculation;
        $obj->device = $device;
        $obj->stats = $device->statistics()->where('calculation_id', $device->pivot->calculation_id)->get();
        $obj->color = "rgb(" . join(",", [random_int(0, 255), random_int(0, 255), random_int(0, 255)]) . ")";
        $obj->avg_length = $device->pivot->avg_length;
        $obj->sum_length = $device->pivot->sum_length;
        $obj->median_length = $device->pivot->median_length;
        $obj->point_style = $this->getPointStyle($simulation, $device);
        $obj->stables = $withStable ? $device->statistics()->where('calculation_id', $device->pivot->calculation_id)->stable()->get() : null;
        return $obj;
    }

    public function compare(Request $request, $calculationId) {
        /** @var Calculation $calculation */
        $calculation = Calculation::with('simulation')->findOrFail($calculationId);
        $simulation = $calculation->simulation;

        $scenario = $request->get('scenario');
        $seed = $request->get('seed');
        $withGlobal = $request->get('global');
        $compareCalculation = $request->get('calculation');
        $sets = [];
        $device = $calculation->getWorldDevice();
        $sets[] = $this->makeSet($device, $simulation, $calculation);
        if ($withGlobal) {
            foreach( $calculation->getGlobalDevices() as $gd) {
                $sets[] = $this->makeSet($gd, $simulation, $calculation, false);
            }
        }

        if ($compareCalculation) {
            $c = Calculation::findOrfail($compareCalculation);
            $d = $c->getWorldDevice();
            $sets[] = $this->makeSet($d, $c->simulation, $c);
            if ($withGlobal) {
                foreach( $c->getGlobalDevices() as $gd) {
                    $sets[] = $this->makeSet($gd, $c->simulation, $c, false);
                }
            }
        }

        $query = Calculation::query();
        if ($scenario)
            $query->whereRelation('simulation', 'scenario', $scenario);
        if ($seed)
            $query->whereRelation('simulation','seed', $seed);

        $calculationOptions = $query->whereNot('id', $calculationId)->pluck('simulation_id','id');

        $sameSimulationCount = Simulation::where('scenario', $simulation->scenario)->count();
        $sameSeedCount = Simulation::where('scenario', $simulation->scenario)->where('seed', $simulation->seed)->count();

        return view('calculation.compare', [
            'withGlobal' => $withGlobal,
            'simulation' => $simulation,
            'calculation' => $calculation,
            'scenarios' => Simulation::scenarios(),
            'scenario' => $scenario,
            'seed' => $seed,
            'simCount' => $sameSimulationCount - 1,
            'seedCount' => $sameSeedCount - 1,
            'sets' => $sets,
            'calculationOptions' => $calculationOptions,
            'compareCalculation' => $compareCalculation,
        ]);
    }
}
