<?php
namespace YmlMau\RuntimeIntegrity\Reporting;

use YmlMau\RuntimeIntegrity\Support\Uuid;

final class ReportEvent
{
    public static function create(array $event)
    {
        if (empty($event['event_id'])) {
            $event['event_id'] = Uuid::v4();
        }
        if (empty($event['timestamp'])) {
            $event['timestamp'] = gmdate('c');
        }
        return $event;
    }
}
