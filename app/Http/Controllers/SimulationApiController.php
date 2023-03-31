<?php

namespace App\Http\Controllers;

use App\Http\Requests\SimulationStartRequest;
use App\Jobs\ProcessAccuracyLog;
use App\Jobs\ProcessGroupArchive;
use App\Jobs\ProcessReceiveArchive;
use App\Jobs\ProcessSendArchive;
use App\Jobs\ProcessSimulationLog;
use App\Models\Device;
use App\Models\Simulation;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SimulationApiController extends BaseController
{
    use DispatchesJobs, ValidatesRequests;

    public function index() {
        return Simulation::orderBy('id', 'desc')->get(['id', 'scenario', 'seed', 'start', 'status', 'simulation_options', 'person_count']);
    }

    public function start(SimulationStartRequest $request)
    {
        $data = array_merge(
            $request->safe([
                'scenario',
                'seed',
                'device_name',
                'version',
                'algorithm',
                'mode',
                'recording',
                'platform',
                'os',
                'broadcast_interval',
                'app_interval',
                'person_count',
                'receive_accuracy',
            ]),
            [
                'simulation_options' => json_decode($request->get('simulation_options')),
                'start' => now(),
                'status' => 'started',
                'ip' => $request->ip(),
                'processes_count' => 0
            ]
        );
        $sim = Simulation::create($data);
        return ['id' => $sim->id];
    }


    protected function getSimulation($id) {
        $sim = Simulation::findOrFail($id);
        return $sim;
    }

    public function simulationLog($id) {
        $sim = $this->getSimulation($id);
        return $sim->log;
    }

    public function log($id, Request $request) {

        $start = microtime(true);

        Simulation::where('id', $id)
            ->update([
                'log' => DB::raw("IF(log IS NULL, '" . $request->log . "\n', CONCAT(log, '" . $request->log . "\n'))")
            ]);
        //$time_elapsed_secs = microtime(true) - $start;
        //$sum = count($toEnter);
        //Log::info("accuracies " . $id . ": " . $sum . " messages in ". number_format($time_elapsed_secs, 2) . " s " . number_format((($time_elapsed_secs / $sum) * 10000 ), 2) . " s/10000");

    }


    public function accuracy($id, Request $request) {

        if (!$request->log) return;
        $start = microtime(true);

        $toEnter = [];
        foreach ($request->log as $entry) {
            $toEnter[] = [
                'simulation_id' => $id,
                'timestep' => $entry['t'],
                'time' => $entry['time'],
                'accuracy' => $entry['accuracy'],
                'person_count' => $entry['person_count'],
            ];
        }

        DB::table('accuracies')->insertOrIgnore($toEnter);
        $time_elapsed_secs = microtime(true) - $start;
        $sum = count($toEnter);
        Log::info("accuracies " . $id . ": " . $sum . " messages in ". number_format($time_elapsed_secs, 2) . " s " . number_format((($time_elapsed_secs / $sum) * 10000 ), 2) . " s/10000");
    }

    public function end($id, Request $request)
    {
        Simulation::where('id', $id)->update([
            'end' => now(),
            'end_time' => $request->end_time,
            'status' => $request->status ?? 'ended'
        ]);
    }

    public function importLogFile($id) {
        ProcessSimulationLog::dispatch(
            $id
        )->onQueue('import');
    }

    public function logFile(int $id, Request $request) {
        $this->storeFile($id, $request);
        ProcessSimulationLog::dispatch(
            $id
        )->onQueue('import');
    }

    public function accuracyFile(int $id, Request $request) {
        $this->storeFile($id, $request);

        ProcessAccuracyLog::dispatch(
            $id
        )->onQueue('import');
    }

    public function sendFile(int $id, Request $request) {
        $this->handleFileRequest($id, $request, "send", ProcessSendArchive::class);
    }

    public function receivedFile(int $id, Request $request) {
        $this->handleFileRequest($id, $request, "received", ProcessReceiveArchive::class);
    }

    public function groupFile(int $id, Request $request) {
        $this->handleFileRequest($id, $request, "group", ProcessGroupArchive::class);
    }

    protected function storeFile(int $id, Request $request, $folder = null) {
        $file = $request->file('file');
        if ($file == null)
            abort(400, "File missing");

        $path = 'simulations' . DIRECTORY_SEPARATOR . $id . ($folder ? DIRECTORY_SEPARATOR . $folder : '');
        $file->storeAs(
            $path,
            $file->getClientOriginalName()
        );
    }
    protected function handleFileRequest(int $id, Request $request, string $folder, $job) {

        $this->storeFile($id, $request, $folder);
        $sim = $this->getSimulation($id);

        Simulation::where('id', $id)->update([
            'processes_count' => DB::raw( 'processes_count + 1' )
        ]);

        if ($sim->status === 'completed') {
            Simulation::where('id', $id)->update([
                'status' => 'processing'
            ]);
        }

        $data = [];
        switch ($folder) {
            case 'send':
                $data['send_status'] = 'uploaded';
                $data['send_count'] = 0;
                break;
            case 'received':
                $data['received_status'] = 'uploaded';
                $data['received_count'] = 0;
                break;
            case 'group':
                $data['group_status'] = 'uploaded';
                $data['group_count'] = 0;
                break;
        }

        Device::upsert( [
            array_merge([
                'simulation_id' => $sim->id,
                'global_name' => $request->name,
                'global_id' => $request->global_id,
                'local_id' => $request->local_id,
                'type' => $request->device_type,
            ], $data)],
            [array_keys($data)]
        );

        /** @var Device $device */
        $device = Device::identifier($sim->id, $request->name, $request->global_id, $request->local_id, $request->device_type)
            ->first();


        if (!$device)
            abort(503, "Cant find inserted device");

        $job::dispatch(
            $device->id
        )->onQueue('import');

        Log::info("Fetched " . $folder . " ". $sim->id ."|". $device->getNameAttribute(). ": ". $sim->id ."|".$device->id);
    }

    public function screenshot($id, Request $request){
        $file = $request->file('file');
        if ($file == null)
            abort(400, "File missing");

        $sim = $this->getSimulation($id);
        $path = $file->storeAs(
            'simulations'. DIRECTORY_SEPARATOR . $id,
            $file->getClientOriginalName()
        );
    }


}
