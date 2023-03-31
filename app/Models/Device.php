<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * @property Simulation $simulation
 */
class Device extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $casts = [
        'type' => DeviceType::class
    ];

    public function simulation() {
        return $this->belongsTo(Simulation::class);
    }

    public function statistics() {
        return $this->hasMany(Statistic::class);
    }

    public function receivedMessages() {
        return $this->hasMany(ReceivedMessage::class);
    }

    public function sendMessages() {
        return $this->hasMany(SendMessage::class);
    }

    public function calculations() {
        return $this->belongsToMany(
            Calculation::class,
            'calculation_device'
        )->withPivot('avg_length', 'sum_length', 'median_length');
    }

    // Queries
    public function scopeComparableDevices($query, $device) {
        $sim = $device->simulation;
        return $query->
            whereHas('simulation', function(Builder $subQuery) use ($sim) {
                $subQuery
                    ->where('scenario', $sim->scenario)
                    ->where('seed', $sim->seed);
            })->device($device);
    }

    public function scopeDevice(Builder $query, Device $device) {
        return $query->where('global_name', $device->global_name)
            ->where('global_id', $device->global_id)
            ->where('local_id', $device->local_id);
    }

    public function scopeIdentifier(Builder $query, $simId, $name, $gId, $lId, $t) {
        return $query->where('simulation_id', $simId)
            ->where('global_name', $name)
            ->where('global_id', $gId)
            ->where('local_id', $lId)
            ->where('type', $t);
    }
    public function scopeNotLocal(Builder $query) {
        return $query->whereNull('local_id');
    }

    public function scopeWorld(Builder $query) {
        return $query->whereNull('global_name');
    }

    public function scopeGlobal(Builder $query) {
        return $query->whereNull('local_id')
            ->whereNotNull('global_name');
    }

    public function scopeBeacon(Builder $query) {
        return $query->where('type', 'b');
    }

    protected function setCount($name, $value) {
        self::where('id', $this->id)
            ->update([$name.'_count' => $value]);
    }

    public function sendCount($value) {
        $this->setCount("send", $value);
    }

    public function receivedCount($value) {
        $this->setCount("received", $value);
    }

    public function groupCount($value) {
        $this->setCount("group", $value);
    }

    protected function updateStatus($name, $status) {
        self::where('id', $this->id)
            ->update([$name.'_status' => $status]);
    }

    protected function updateSendStatus($status) {
        $this->updateStatus('send', $status);
    }

    protected function updateReceivedStatus($status) {
        $this->updateStatus('received', $status);
    }

    protected function updateGroupStatus($status) {
        $this->updateStatus('group', $status);
    }

    // Actions
    public function sendUnzipped() {
        $this->updateSendStatus('unzipped');
    }

    public function receivedUnzipped() {
        $this->updateReceivedStatus('unzipped');
    }

    public function groupUnzipped() {
        $this->updateGroupStatus('unzipped');
    }

    public function sendProcessing() {
        $this->simulation->onDeviceProcessing();
        $this->updateSendStatus('processing');
    }

    public function receivedProcessing() {
        $this->simulation->onDeviceProcessing();
        $this->updateReceivedStatus('processing');
    }

    protected function onComplete() {
        $this->simulation->onDeviceInsertCompleted();
    }

    public function sendCompleted() {
        $this->updateSendStatus('completed');
        $this->onComplete();
    }

    public function receivedCompleted() {
        $this->updateReceivedStatus('completed');
        $this->onComplete();
    }

    public function groupCompleted() {
        $this->updateGroupStatus('completed');
        $this->onComplete();

        // Use SetAllGroupSenderDevices instead
        /*
        $devices = self::query()
            ->where('simulation_id', $this->simulation_id)
            ->where('global_name', 'person')
            ->pluck('id', 'global_id');

        $toUpdate = DB::table('group_logs_receivers')
            ->select('sender_id')
            ->whereIn('device_id', $devices)
            ->whereNull('sender_device_id')
            ->distinct()
            ->get();

        foreach ($toUpdate as $entry) {
            if (!isset($devices[$entry->sender_id]))
                continue;

            DB::table('group_logs_receivers')
                ->whereIn('device_id', $devices)
                ->whereNull('sender_device_id')
                ->where('sender_id', $entry->sender_id)
                ->update(['sender_device_id' => $devices[$entry->sender_id]]);
        }
        */
    }


    public function getNameAttribute() {
        if (!$this->global_name)
            return "World";

        return $this->global_name . "-". $this->global_id . ($this->local_id === null ? "" : "-" . $this->local_id);
    }

    public function isWorld() {
        return $this->global_name === null;
    }

    public function isLocal() {
        return $this->local_id !== null;
    }
    public function isGlobal() {
        return $this->global_id !== null && $this->local_id === null;
    }

    public function isGenerated() {
        return $this->type === DeviceType::Generated;
    }

    public function parent() {
        if ($this->isWorld()) return null;

        $query = Device::query()
            ->where('simulation_id', $this->simulation_id);

        if ($this->isGlobal()) {
            return $query->whereNull('global_name')->first();
        }

        $query->where('global_name', $this->global_name)
            ->whereNull('local_id');

        $query->orderBy('global_name')
            ->orderBy('global_id')
            ->orderBy('local_id');

        return $query->first();
    }

    public function children() {

        if ($this->isLocal()) {
            return [];
        }

        $query = Device::query()->where('simulation_id', $this->simulation_id);

        if ($this->isWorld()) {
            $query
                ->whereNotNull('global_name')
                ->whereNotNull('global_id')
                ->whereNull('local_id');
        } else
            $query->where('global_name', $this->global_name);


        if ($this->isGlobal()) {
                $query
                    ->where('global_id', $this->global_id)
                    ->whereNotNull('local_id');
        }

        $query->orderBy('global_name')
            ->orderBy('global_id')
            ->orderBy('local_id');

        return $query->get();
    }

    public function getZipFilePath(string $folder) {
        return $this->getCsvFilePath($folder) . ".zip";
    }

    public function getCsvFilePath(string $folder) {
        return $this->getBasePath($folder) . $this->getFileName() . ".csv";
    }


    public function getBasePath(string $folder) {
        return "simulations"
            . DIRECTORY_SEPARATOR
            . $this->simulation_id
            . DIRECTORY_SEPARATOR
            . $folder
            . DIRECTORY_SEPARATOR;
    }

    protected function getFileName() {
        return $this->global_name . "-" . $this->global_id . "-" . $this->local_id;
    }

}
