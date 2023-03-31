<?php

namespace App\Http\Requests;

use App\Models\DeviceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class SimulationReceivedRequest extends FormRequest
{
    public function rules()
    {
        return [
            'device_id' => 'required|numeric',
            'type' => ['required',new Enum(DeviceType::class)],
            'packages' => 'required|array',
            'packages.*.id' => 'required|numeric',
            'packages.*.time' => 'required|numeric',
            'packages.*.uuid' => 'required|numeric',
            'packages.*.position.x' => 'required|numeric',
            'packages.*.position.y' => 'required|numeric',
            'packages.*.position.z' => 'required|numeric',
            'packages.*.distance' => 'required|numeric',
            'packages.*.value' => 'required',
        ];
    }
}
