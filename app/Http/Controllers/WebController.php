<?php

namespace App\Http\Controllers;

use App\Jobs\CleanupJob;
use App\Jobs\DeleteReceivedMessages;
use App\Jobs\ResetSimulation;
use App\Models\Device;
use App\Models\Simulation;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class WebController  extends BaseController
{

    public function reset()
    {
        $simulations = Simulation::all();

        foreach($simulations as $simulation) {
            ResetSimulation::dispatch($simulation->id)
                ->onQueue('high');
        }

        return redirect('/');
    }
    public function index() {
        $receivedMessages = Device::query()
            ->sum('received_count');

        $statisticCount = DB::table('calculation_device')
            ->sum('statistic_count');

        return view('index', [
            'receivedCount' => $receivedMessages,
            'statisticCount' => $statisticCount,
            'queueSize' => Queue::size('import') + Queue::size('high'),
            'isIndex' => true
        ]);
    }

    public function download() {
        return view('download');
    }

    public function cleanup() {

        CleanupJob::dispatch()->onQueue('high');

        return redirect('/');
    }

}
