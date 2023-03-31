<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calculation extends Model
{
    use HasFactory;
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    public function simulation() {
        return $this->belongsTo(Simulation::class);
    }

    public function devices() {
        return $this->belongsToMany(
            Device::class,
            'calculation_device'
        )->withPivot('statistic_count', 'avg_length', 'sum_length', 'median_length')
            ->orderBy('global_name')
            ->orderBy('global_id')
            ->orderBy('local_id');
    }

    public function getWorldDevice(): Device {
        return $this->devices()->world()->first();
    }

    public function getGlobalDevices() {
        return $this->devices()->global()->get();
    }

    public function statistics()
    {
        return $this->hasMany(Statistic::class);
    }

    public function scopeScenario(Builder $query, $scenario) {
        return $query->whereRelation('simulation', 'scenario', $scenario);
    }

    public function scopeSeed(Builder $query, $seed) {
        return $query->whereRelation('simulation', 'seed', $seed);
    }

    public function scopeHasCalculations(Builder $query, $globalName, $globalId, $localId) {
        return $query->whereHas('devices', function(Builder $query) use ($globalName, $globalId, $localId) {
            $query->where('global_name', $globalName)
                ->where('global_id', $globalId);

            if ($localId != null)
                $query->where('local_id', $localId);

            return $query;
        });
    }

    function avgLength() {
        return $this->statistics->avg('length');
    }
    function sumLength() {
        return $this->statistics->sum('length');
    }
    function medLength() {
        return $this->statistics->median('length');
    }

}
