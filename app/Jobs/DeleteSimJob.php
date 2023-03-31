<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\Simulation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteSimJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 9999999;

    public int $simId;
    public function __construct($simId)
    {
        $this->simId = $simId;
    }


    public function handle()
    {

        $device = Device::query()
            ->where('simulation_id', $this->simId)
            ->first();

        if (!$device) {
            $start = microtime(true);

            $simulation = Simulation::find($this->simId);
            if ($simulation) {
                $simulation->delete();
                $time_elapsed_secs = number_format(microtime(true) - $start, 2);
                Log::info("Deleted " . $this->simId . " in " . $time_elapsed_secs . " s");
            } else {
                Log::info("Deleted " . $this->simId . " not found - ignoring");
            }

            $this->job->delete();
             return;
        }

        $start = microtime(true);
        $limit = 1000;
        $total = 0;
        do {
            $deletedRows = DB::table('group_logs_receivers')
                ->where('device_id', $device->id)
                ->orWhere('sender_device_id', $device->id)
                ->limit($limit)
                ->delete();
            $total+= $deletedRows;
        } while($total < 10000 && $deletedRows > 0);
        $time_elapsed_secs = number_format(microtime(true) - $start, 2);
        if ($total > 0)
            Log::info("Deleted group logs receivers " . $this->simId . "|" . $device->id . ": " . $total . " messages in " . $time_elapsed_secs . " s");

        if ($deletedRows > 0) {
            $this->release();
            return;
        }

        $start = microtime(true);
        $limit = 2000;
        $total = 0;
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        do {
            $deletedRows = DB::table('group_logs')->where('device_id', $device->id)->limit($limit)->delete();
            $total+= $deletedRows;
        } while($total < $limit*50 && $deletedRows > 0);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $time_elapsed_secs = number_format(microtime(true) - $start, 2);
        if ($total > 0)
            Log::info("Deleted group logs " . $this->simId . "|" . $device->id . ": " . $total . " messages in " . $time_elapsed_secs . " s");

        if ($deletedRows > 0) {
            $this->job->release();
            return;
        }

        // Received
        $limit = 10000;
        $total = 0;
        $start = microtime(true);
        do {
            $deletedRows = DB::table('received_messages')->where('device_id', $device->id)->limit($limit)->delete();
            $total+= $deletedRows;
        } while($total < $limit*50 && $deletedRows > 0);

        $time_elapsed_secs = number_format(microtime(true) - $start, 2);
        if ($total > 0)
            Log::info("Deleted received " . $this->simId . "|" . $device->id . ": " . $total . " messages in " . $time_elapsed_secs . " s");

        if ($deletedRows > 0) {
            $this->job->release();
            return;
        }

        // send messages
        $total = 0;
        $start = microtime(true);
        do {
            $deletedRows = DB::table('send_messages')->where('device_id', $device->id)->limit($limit)->delete();
            $total+= $deletedRows;
        } while($total < $limit*50 && $deletedRows > 0);
        $time_elapsed_secs = number_format(microtime(true) - $start, 2);
        if ($total > 0)
            Log::info("Deleted send " . $this->simId . "|" . $device->id . ": " . $total . " messages in " . $time_elapsed_secs . " s");

        // Statistics etc
        $total = 0;
        $start = microtime(true);
        $total += DB::table('statistics')->where('device_id', $device->id)->delete();
        $total += DB::table('calculation_device')->where('device_id', $device->id)->delete();
        $total += DB::table('series')->where('device_id', $device->id)->delete();

        $time_elapsed_secs = number_format(microtime(true) - $start, 2);
        if ($total > 0)
            Log::info("Deleted statistics " . $this->simId . "|" . $device->id . ": " . $total . " in " . $time_elapsed_secs . " s");

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $start = microtime(true);
        $device->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $time_elapsed_secs = number_format(microtime(true) - $start, 2);
        Log::info("Deleted Device " . $this->simId . "|" . $device->id . " in " . $time_elapsed_secs . " s");
        $this->job->release();
    }
}
