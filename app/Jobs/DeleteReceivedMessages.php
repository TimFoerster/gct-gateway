<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\ReceivedMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteReceivedMessages implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $simulationId;
    public int $deviceId;
    public int $uniqueFor = 1800; // unique for 30 mins
    public $tries = 255;

    public function __construct(int $simulationId, int $device)
    {
        $this->simulationId = $simulationId;
        $this->deviceId = $device;
    }

    public function handle()
    {
        $limit = 1000;
        $total = 0;
        $start = microtime(true);
        do {
            $deletedRows = ReceivedMessage::where('device_id', $this->deviceId)->limit($limit)->delete();
            $total+= $deletedRows;
        } while($total < 50000 && $deletedRows == $limit);

        $time_elapsed_secs = number_format(microtime(true) - $start, 2);

        Log::info("Deleted " . $this->simulationId . "|" . $this->deviceId . ": " . $total . " messages in " . $time_elapsed_secs . " s");

        if ($deletedRows == $limit) {
            $this->job->release();
            return;
        }
        Device::query()
            ->where('id', $this->deviceId)
            ->update(['received_status' => 'cleared']);
    }

    public function uniqueId()
    {
        return $this->deviceId;
    }
}
