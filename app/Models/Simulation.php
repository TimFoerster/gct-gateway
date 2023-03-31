<?php

namespace App\Models;

use App\Jobs\SetAllGroupSenderDeviceIds;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use stdClass;

class Simulation extends Model
{
    use HasFactory;

    protected $hidden = ['log'];
    protected $guarded = [];
    protected $casts = [
        'simulation_options' => 'array',
    ];

    protected static function getValues($column) {
        return self::select($column, DB::raw('COUNT(*) as c'))->groupBy($column)->orderBy($column)->pluck('c', $column);
    }
    static function scenarios() {
        return self::getValues('scenario');
    }

    public static function seeds()
    {
        return self::getValues('seed');
    }

    public static function appUpdates() {
        return self::getValues('app_update');
    }

    public static function algorithms() {
        return self::getValues('algorithm');
    }

    protected $tree = null;
    public function nestedDeviceTree() {
        if ($this->tree != null) {
            return $this->tree;
        }
        $devices = $this->devices;

        $root = null;
        $globals = [];
        $locals = [];

        /** @var Device $device */
        foreach ($devices as $device) {
            if ($device->isWorld()) {
                $root = $device;
            }
            if ($device->isGlobal()) {
                $globals[$device->global_name . "-".$device->global_id] = $device;
            }
            if($device->isLocal()) {
                $parentUuid = $device->global_name . "-".$device->global_id;
                if (!isset($locals[$parentUuid]))
                    $locals[$parentUuid] = [];

                $locals[$parentUuid][] = $device;
            }
        }

        $obj = new stdClass();
        $obj->world = $root;
        $obj->globals = [];
        $count = 0;
        foreach ($globals as $key => $global) {
            $globalObject = new stdClass();
            $globalObject->device = $global;
            $globalObject->locals = $locals[$key];
            $c = count($locals[$key]);
            $globalObject->local_count = $c;
            $count += $c;
            $obj->globals[] = $globalObject;
        }

        $obj->local_count = $count;

        $this->tree = $obj;
        return $obj;
    }

    public static function auis()
    {
        return self::getValues('app_interval');
    }


    public function devices() {
        return $this
            ->hasMany(Device::class)
            ->orderBy('global_name', 'asc')
            ->orderBy('global_id', 'asc')
            ->orderBy('local_id', 'asc');
    }
    public function calculations() {
        return $this->hasMany(Calculation::class);
    }

    public function completed() {
        return $this->status !== 'started';
    }

    public function onDeviceInsertCompleted() {
        self::where('id', $this->id)
            ->update(['processes_count' => DB::raw( 'processes_count - 1' )]);
        $this->refresh();

        if ($this->processes_count <= 0 && ($this->status === 'processing' || $this->status === 'completed')) {
            Log::info("Import Sim " . $this->id . ": completed");
            self::where('id', $this->id)->update(
                ['status' => 'processed', 'processes_count' => 0]
            );

            SetAllGroupSenderDeviceIds::dispatch($this->id)->onQueue('import');
        }
    }

    public function getLogAttribute() {
        return DB::table('simulation_log')
            ->where('simulation_id', $this->id)
            ->pluck('message');
    }

}
