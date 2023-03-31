<?php

namespace App\Jobs;

use Illuminate\Support\Facades\DB;

class ProcessSimulationLog extends GenericSimulationJob
{
    protected string $entry = 'log';

    protected function action($chunk)
    {
        DB::table('simulation_log')->insertOrIgnore($chunk);
    }
}
