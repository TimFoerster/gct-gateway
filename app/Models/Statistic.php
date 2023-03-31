<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Statistic extends Model
{
    use HasFactory;
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    public function scopeDevice(Builder $query, Device $device = null) {
        return $query->whereHas('device', function(Builder $query) use ($device) {
            $query->where('global_name', $device->global_name)
                ->where('global_id', $device->global_id)
                ->where('local_id', $device->local_id);
        });
    }

    public function scopeStable(Builder $query) {
        return $query
            ->where('length','>',0.999999)
            ->where('unique_packages', '>', 1);
    }

    public function isStable() {
        return $this->length > 0.999999 && $this->unique_packages > 1;
    }

    public function diff($prev) {
        return min([abs(bcsub($prev->value, $this->value)), abs(bcsub($this->value, $prev->value))]);
        switch (bccomp($prev->value, $this->value)) {
            case 1: return bcsub($prev->value, $this->value);
            case -1: return bcsub($this->value, $prev->value);
        }

        return 0;
    }
}
