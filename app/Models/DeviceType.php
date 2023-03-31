<?php

namespace App\Models;

enum DeviceType: string
{
    case Device = 'd';
    case Beacon = 'b';
    case Generated = 'g';

    public function name(): string {
        return match($this)
        {
            self::Device => 'Device',
            self::Beacon => 'Beacon',
            self::Generated => 'Beacon',
        };
    }
}
