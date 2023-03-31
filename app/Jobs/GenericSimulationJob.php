<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

abstract class GenericSimulationJob extends CsvImportJob
{
    protected string $entry;
    protected string $filename;

    public function __construct(
        int $simId,
    )
    {
        $this->simulation_id = $simId;
    }

    public function uniqueId()
    {
        return $this->entry . "-" . $this->simulation_id;
    }

    public function handle()
    {
        $this->filename = $this->entry.".csv";
        $this->csvFile = $this->getBasePath() . $this->filename;
        $this->zipFile = $this->csvFile . ".zip";

        if ($this->attempts() == 0) {
            Cache::put($this->uniqueId(), 0);
        }

        try {
            $this->unpack();
            $this->work();
        } catch (\Throwable $e) {
            Log::error($this->entry. " " . $this->simulation_id . ": could not be completed");
            throw $e;
        }

    }


    protected function processed($count) {
        Cache::set($this->uniqueId(), $count);
    }

    protected function completed() {
        Cache::forget($this->uniqueId());
    }

    protected function count() {
        return Cache::get($this->uniqueId(), 0);
    }

    protected function mergeData(){
        return ['simulation_id' => $this->simulation_id];
    }

    protected function logString()
    {
        return $this->entry. " ". $this->simulation_id;
    }

}
