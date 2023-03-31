<?php

namespace App\Jobs;

use Illuminate\Support\Facades\DB;

class ProcessAccuracyLog extends GenericSimulationJob
{
    protected string $entry = 'accuracy';

    protected function action($chunk)
    {
        DB::table('accuracies')->insertOrIgnore($chunk);
    }

}
