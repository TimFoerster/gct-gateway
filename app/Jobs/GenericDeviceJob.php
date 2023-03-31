<?php

namespace App\Jobs;

use App\Models\Device;
use Illuminate\Support\Facades\Log;

abstract class GenericDeviceJob extends CsvImportJob
{
    protected int $deviceId;
    protected string $folder;

    protected Device $device;

    public function __construct(
        int $deviceId,
    )
    {
        $this->deviceId = $deviceId;

    }
    public function uniqueId()
    {
        return $this->folder . "-" . $this->deviceId;
    }

    public function getBasePath()
    {
        return parent::getBasePath() . $this->folder . DIRECTORY_SEPARATOR;
    }

    public function handle()
    {
        /** @var Device $device */
        $device = $this->device = Device::findOrFail($this->deviceId);
        $this->simulation_id = $device->simulation_id;
        $this->zipFile = $device->getZipFilePath($this->folder);
        $this->csvFile = $device->getCsvFilePath($this->folder);

        try {
            if (!$this->unpack())
                return;
            $this->work();
        } catch (\Throwable $e) {
            Log::error($this->folder. " " . $this->simulation_id . "|".$this->deviceId.": could not be completed");
            throw $e;
        }
    }

    protected function mergeData(){
        return [
            'device_id' => $this->deviceId
        ];
    }

    protected function logString()
    {
        return $this->folder . " " . $this->simulation_id . "|" . $this->deviceId;
    }
}
