<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\ReceivedMessage;
use App\Models\Simulation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SetAllGroupSenderDeviceIds implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 10;
    public $simId;

    public function __construct($simId = null)
    {
        $this->simId = $simId;
    }

    public function handle()
    {
        $start = microtime(true);

        $devices = Device::query()
            ->where('simulation_id', $this->simId)
            ->where('global_name', 'person')
            ->pluck('id', 'global_id');

        $toUpdate = DB::table('group_logs_receivers')
            ->select("sender_id")
            ->whereIn('device_id', $devices)
            ->distinct()
            ->pluck("sender_id");

        Log::info("group senders " . $this->simId. ": Updating ". count($toUpdate). " devices.");

        $count = 0;
        foreach ($toUpdate as $senderId) {
            if (!isset($devices[$senderId])) {
                Log::warning("group senders " . $this->simId . " sender_id ". $senderId. " not found in simulation.");
                continue;
            }

            $start2 = microtime(true);
            $rc = 0;
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            do {
                $senderDeviceId = $devices[$senderId];
                $affectedRows = DB::table('group_logs_receivers')
                    ->whereIn('device_id', $devices)
                    ->where('sender_id', $senderId)
                    // is sender_device_id != value or null
                    ->whereRaw("IFNULL(sender_device_id,0) <> " . $senderDeviceId)
                    ->limit(5000)
                    ->update(['sender_device_id' => $senderDeviceId]);
                $rc += $affectedRows;

            } while($affectedRows > 0);
            DB::statement('SET FOREIGN_KEY_CHECKS=1');


            $count += $rc;
            if ($rc > 0) {
                $time_elapsed_secs2 = number_format(microtime(true) - $start2, 2);
                Log::info("group senders " . $this->simId ."|". $devices[$senderId] ." ". $rc. " entries in ".$time_elapsed_secs2." s");
            }

        }

        $time_elapsed_secs = number_format(microtime(true) - $start, 2);

        Log::info("group senders " . $this->simId .": ". $count. " entries in ".$time_elapsed_secs." s");
    }
}
