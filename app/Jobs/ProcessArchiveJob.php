<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\Simulation;
use App\Support\CsvHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

abstract class ProcessArchiveJob implements ShouldQueue, ShouldBeUnique
{
    use CsvHelper, Dispatchable, InteractsWithQueue, Queueable;

    public function handle()
    {
        /** @var Device $device */
        $this->device = $device = Device::find($this->deviceId);
        if ($device == null) {
            Log::warning("Device " . $this->deviceId . " is gone.");
            $this->job->delete();
            return;
        }

        $zipFile = $device->getZipFilePath($this->folder);
        $csvFile = $device->getCsvFilePath($this->folder);

        if (!Storage::exists($csvFile)) {
            if (!Storage::exists($zipFile)) {
                Log::info("Could either find zip nor csv file " . $zipFile);
                $this->job->release(5);
            }
            $zip = new \ZipArchive();
            $zip->open(Storage::path($zipFile));
            $zip->extractTo(Storage::path($device->getBasePath($this->folder)));
            $zip->close();
        }
        
        $completed = $this->writeCsvToDatabase($this->count(), Storage::path($csvFile), [$this, 'action'], [$this, 'processed']);
        if (!$completed) {
            $this->job->release();
            Log::info("Released " . $this->device->simulation_id . "|" . $this->deviceId . " " . $this->folder . " job");
            return;
        }
        Storage::delete($csvFile);
        $this->completed();
    }

    abstract protected function action($chunk);
    abstract protected function count();
    abstract protected function unzipped();
    abstract protected function completed();
    abstract protected function processed(int $count);
}
