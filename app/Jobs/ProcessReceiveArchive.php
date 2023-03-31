<?php

namespace App\Jobs;

use App\Models\ReceivedMessage;

class ProcessReceiveArchive extends GenericDeviceJob
{
    protected string $folder = "received";

    protected function action($chunk)
    {
        ReceivedMessage::insertOrIgnore($chunk);
    }


    protected function count() {
        return $this->device->received_count;
    }

    protected function completed() {
        $this->device->receivedCompleted();
    }

    protected function processed(int $count) {
        $this->device->receivedCount($count);
    }


}
