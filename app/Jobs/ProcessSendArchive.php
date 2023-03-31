<?php

namespace App\Jobs;

use App\Models\SendMessage;

class ProcessSendArchive extends GenericDeviceJob
{
    protected string $folder = "send";

    protected function action($chunk) {
        SendMessage::insertOrIgnore($chunk);
    }

    protected function count() {
        return $this->device->send_count;
    }

    protected function completed() {
        $this->device->sendCompleted();
    }

    protected function processed(int $count) {
        $this->device->sendCount($count);
    }

}
