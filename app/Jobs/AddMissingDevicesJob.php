<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\ReceivedMessage;
use App\Models\Simulation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AddMissingDevicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $simId;

    public function __construct($simId = null)
    {
        $this->simId = $simId;
    }

    public function handle()
    {
        if ($this->simId === null)
            $simulations = Simulation::query()
                ->where('status', 'processed')
                ->whereHas('calculations', fn($subquery) =>
                    $subquery->whereIn('status', ['completed', 'ignore', 'ignored'])
                )
                ->get();
        else
            $simulations = Simulation::query()
                ->where('id', $this->simId)
                ->get();

        foreach ($simulations as $simulation) {
            $devices = $simulation->devices()
                ->where('global_name', 'person')
                ->get();

            $personCount = $simulation->person_count;

            $created = [];
            for ($i = 1; $i <= $personCount; $i++) {
                $el = $devices->firstWhere('global_id', $i);
                if ($el) continue;
                $dev = Device::create([
                    'simulation_id' => $simulation->id,
                    'global_name' => 'person',
                    'global_id' => $i,
                    'local_id' => 0,
                    'type' => 'd',
                    'group_status' => 'generated',
                    'group_count' => 0
                ]);
                $created[] = $i.":" . $dev->id;
            }

            if (count($created) > 0)
                Log::info("S".$simulation->id ."| Created " . count($created) ." person devices: " . join(', ', $created));
        }
    }
}
