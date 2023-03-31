<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SimulationStartRequest extends FormRequest
{

    public function rules()
    {
        return [
            'scenario' => 'required',
            'seed' => 'required|numeric',
            'device_name' => 'string',
            'version' => 'string|required',
            'algorithm' => 'string|required',
            'mode' => 'string',
            'recording' => 'nullable|string',
            'platform' => 'string',
            'os' => 'string',
            'broadcast_interval' => 'numeric',
            'app_interval' => 'numeric',
            'person_count' => 'numeric',
            'simulation_options' => 'json',
            'receive_accuracy' => 'numeric'
        ];
    }
}
