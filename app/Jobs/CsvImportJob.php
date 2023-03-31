<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

abstract class CsvImportJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected int $simulation_id;

    public int $uniqueFor = 100;

    public $tries = 1024;

    protected string $csvFile;
    protected string $zipFile;

    protected int $chunkSize = 1024;

    protected function unpack() {
        if (!Storage::exists($this->csvFile)) {
            if (!Storage::exists($this->zipFile)) {
                Log::error("Could either find zip nor csv file " . $this->zipFile);
                $this->job->delete();
                $this->completed();
                return false;
            }
            $zip = new \ZipArchive();
            $zip->open(Storage::path($this->zipFile));
            $zip->extractTo(Storage::path($this->getBasePath()));
            $zip->close();
        }

        return true;
    }

    protected function work() {
        $completed = $this->writeCsvToDatabase($this->count());
        if (!$completed) {
            $this->job->release();
            return;
        }
        Storage::delete($this->csvFile);
        $this->completed();
        $this->delete();
    }

    public function getBasePath() {
        return "simulations"
            . DIRECTORY_SEPARATOR
            . $this->simulation_id
            . DIRECTORY_SEPARATOR;
    }

    protected function writeCsvToDatabase(int $offset) {

        $start = microtime(true);

        $handle = fopen(Storage::path($this->csvFile), "r");
        $headerLine = fgets($handle);

        if ($headerLine === false) {
            Log::error("No Line found in the csv file.");
            return 1;
        }

        $headerRow = str_getcsv($headerLine);

        $rows = [];
        $complete = false;
        $sum = 0;

        // skip prev lines
        while($sum < $offset) {
            fgets($handle);
            $sum++;
        }

        $sum = 0;
        do {
            while (count($rows) < $this->chunkSize && !($complete = ($csvRow = fgets($handle)) === false)) {

                $rows[] = array_merge(
                    array_combine($headerRow, str_getcsv($csvRow)),
                    $this->mergeData()
                );
            }

            $this->action($rows);
            $sum += count($rows);
            $this->processed($offset + $sum);
            $rows = [];

        } while(!$complete && ($sum < $this->chunkSize * 50));

        fclose($handle);
        $time_elapsed_secs = microtime(true) - $start;
        if ($sum > 0) {
            Log::info($this->logString().": " . $sum . " entries in ". number_format($time_elapsed_secs, 2) . " s " . number_format((($time_elapsed_secs / $sum) * 10000 ), 2) . " s/10000");
        }

        return $complete;
    }

    abstract protected function mergeData();
    abstract protected function processed(int $count);
    abstract protected function completed();
    abstract protected function count();
    abstract protected function action($chunk);
    abstract protected function logString();



}
