<?php

namespace App\Jobs;

use App\Models\GroupLog;
use App\Models\GroupLogReceiver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessGroupArchive extends GenericDeviceJob
{
    protected string $folder = "group";

    protected int $chunkSize = 64;

    protected function action($chunk)
    {

        $receivedObjects = [];

        foreach ($chunk as $index => $value) {
            $received = explode("|", $value['received']);

            foreach ($received as $key => $r) {
                $entry = explode(":", $r);
                $receivedObjects[] = [
                    'device_id' => $value['device_id'],
                    'group_entry_id' => $value['entry'],
                    'index' => $key + 1,
                    'sender_id' => $entry[0],
                    'message_count' => $entry[1],
                ];
            }

            unset($chunk[$index]['received']);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        GroupLog::upsert($chunk, ['t', 'time', 'gid', 'devices', 'x','y','z']);
        GroupLogReceiver::upsert($receivedObjects, ['message_count']);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    protected function count() {
        return $this->device->group_count;
    }

    protected function completed() {
        $this->device->groupCompleted();
    }

    protected function processed(int $count) {
        $this->device->groupCount($count);
    }


}
