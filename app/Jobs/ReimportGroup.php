<?php

namespace App\Jobs;

use App\Models\Calculation;
use App\Models\Device;
use App\Models\Simulation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReimportGroup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public int $simulationId;

    public function __construct(int $simId)
    {
        $this->simulationId = $simId;
    }

    public function handle()
    {
        $simulation = Simulation::find($this->simulationId);

        if (!$simulation) {
            $this->delete();
            return;
        }

        $devices = Device::query()
            ->where('simulation_id', $this->simulationId)
            ->where('global_name', 'person')
            ->orderBy('id')
            ->pluck('id');

        Device::query()
            ->whereIn('id', $devices)
            ->update(['group_count' => 0]);

        Simulation::query()
            ->where('id', $simulation->id)
            ->update([
                'status' => 'processing',
                'processes_count' => DB::raw('processes_count + '.count($devices))
            ]);

        foreach ($devices as $dId) {
            ProcessGroupArchive::dispatch(
                $dId
            )->onQueue('import');
        }
        Log::info("S".$this->simulationId.": ".count($devices) . " group jobs scheduled.");

        //SetAllGroupSenderDeviceIds::dispatch($this->simulationId);

        $this->delete();
    }
}
