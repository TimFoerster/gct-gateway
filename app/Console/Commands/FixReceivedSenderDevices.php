<?php

namespace App\Console\Commands;

use App\Jobs\SetAllGroupSenderDeviceIds;
use App\Models\Simulation;
use Illuminate\Console\Command;
use Illuminate\Queue\Queue;
use Illuminate\Support\Facades\Log;

class FixReceivedSenderDevices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'group:senders {simulationIds?*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fixes sender';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!$ids = $this->argument('simulationIds'))
            $ids = Simulation::query()->pluck('id');

        foreach ($ids as $sim)
            SetAllGroupSenderDeviceIds::dispatch($sim);

        $this->output->info(count($ids) . " jobs scheduled.");

        return Command::SUCCESS;
    }
}
