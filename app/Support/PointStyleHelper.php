<?php

namespace App\Support;

use App\Models\Device;
use App\Models\Simulation;

trait PointStyleHelper
{

    public function getPointStyle(Simulation $simulation, Device $device) {

        if ($device->isGlobal())
            return "cross";

        switch ($simulation->scenario) {
            case "Office":
                switch ($device->global_id) {
                    case 1: return "star";
                    case 2: return "rect";
                }
            case "Train_half":
                switch ($device->global_id) {
                    case 0: return "triangle";
                    case 1: return "star";
                    case 2: return "rectRot";
                    case 3: return "rectRounded";
                    case 4: return "rect";
                    case 5: return "line";
                    case 6: return "dash";
                    case 7: return "crossRot";
                }
            case "Restaurant_small":
                return "star";
            case "Darmstadium":
                switch ($device->global_name) {
                    case "floor":
                        switch ($device->global_id) {
                            case 0: return "triangle";
                            case 1: return "rect";
                        }
                    case "ferrum": return "line";
                    case "spectrum": return "star";
                }
        }

        return null;
    }
}
