<?php

namespace App\Console\Commands;

use App\Jobs\DeleteSimJob;
use App\Jobs\SetAllGroupSenderDeviceIds;
use App\Models\Simulation;
use Illuminate\Console\Command;
use Illuminate\Queue\Queue;
use Illuminate\Support\Facades\Log;

class DeleteSimulationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simulation:delete {simulationIds?*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes a simulation';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!$ids = $this->argument('simulationIds'))
            $ids = Simulation::query()
                ->whereIn('status', ['error', 'started'])
                ->pluck('id');

        foreach ($ids as $sim)
            DeleteSimJob::dispatch($sim)->onQueue('high');

        $this->output->info(count($ids) . " jobs scheduled.");
        return Command::SUCCESS;
    }
}
