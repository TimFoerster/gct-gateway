<?php

namespace App\Console\Commands;

use App\Jobs\ReimportGroup;
use App\Models\Simulation;
use Illuminate\Console\Command;

class ReimportGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'group:reimport {simulationIds?*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reimports all Groups of simulations';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!$ids = $this->argument('simulationIds'))
            $ids = Simulation::query()
                ->orderBy('id')
                ->pluck('id');

        foreach ($ids as $sim) {
            ReimportGroup::dispatch($sim)->onQueue('high');
            $this->output->info("Scheduled Reimport Group ".$sim);
        }

        return Command::SUCCESS;
    }
}
