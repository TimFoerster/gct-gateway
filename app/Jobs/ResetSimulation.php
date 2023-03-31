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

class ResetSimulation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public int $simulationId;

    public function __construct(int $simulationId)
    {
        $this->simulationId = $simulationId;
    }

    public function handle()
    {
        $simulation = Simulation::findOrFail($this->simulationId);

        foreach ($simulation->calculations as $calc) {
            Calculation::where('id', $calc->id)
                ->update([
                    'status' => 'reset',
                    'end' => null,
                    'calculated_time' => 0
                ]);

            $calc->statistics()->delete();
        }

        $devices = $simulation->devices()
            ->where('type', 'b')
            ->whereNotNull('local_id')
            ->get();

        Simulation::query()->where('id', $simulation->id)
            ->update([
                'status' => 'processing',
                'processes_count' => count($devices)
            ]);

        foreach ($devices as $device) {
            Device::query()->where('id', $device->id)
                ->update(['received_count' => 0, 'received_status' => 'uploaded']);

            ProcessReceiveArchive::dispatch(
                $device->id
            )->onQueue('import');
        }


    }
}
