<?php

namespace App\Jobs;

use App\Models\ReceivedMessage;
use App\Models\Simulation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public function __construct()
    {
    }

    protected function canTruncate() {
        $simulations = Simulation::query()
            ->with('calculations')
            ->orderBy('id', 'desc')
            ->get();

        foreach ($simulations as $sim) {
            // must be processed
            if ($sim->status != 'processed')
                return false;

            // must have at least one calc, when it's not park
            if (count($sim->calculations) == 0 && $sim->scenario != 'Park')
                return false;

            // each calc must be completed
            foreach ($sim->calculations as $calc) {
                if ($calc->status != 'completed')
                    return false;
            }
        }

        return true;
    }

    public function handle()
    {
        $start = microtime(true);

        // Empty table? ignore this job.
        if (ReceivedMessage::query()->first() == null) {
            return;
        }

        // Check truncate, if each sim has a calc with status completed
        // if so, just truncate instead of deleting.
        if ($this->canTruncate()) {

            ReceivedMessage::query()->truncate();
            AddMissingDevicesJob::dispatch();
            $time_elapsed_secs = microtime(true) - $start;
            Log::info("Truncated Received Messages in ". number_format($time_elapsed_secs, 2) . " s ");
            return;
        }

        // Disallow delete queries
        return ;

        $simulations = Simulation::query()
            ->where('status', 'processed')
            ->whereHas('calculations', fn($subquery) =>
                $subquery->where('status', 'completed')
            )
            ->get();

        foreach ($simulations as $simulation) {
            $devices = $simulation->devices()
                ->where('received_status', 'completed')
                ->where('received_count', '>', 0)
                ->get();
            if (count($devices) > 0) {
                Log::info("Cleaning Simulation " . $simulation->id . " " . count($devices). " devices");
                foreach ($devices as $device) {
                    DeleteReceivedMessages::dispatch($simulation->id, $device->id)
                        ->onQueue('high');
                }

                AddMissingDevicesJob::dispatch();
            }
        }
    }
}
