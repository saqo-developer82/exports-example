<?php

namespace Exports\Notifications;

use Exports\Events\EntityExported;

class Notification
{

	public static function sendEntityExportNotification($user, $data){
        if ($user->is_active) {
            config(['queue.default' => 'sync']);

            event(new EntityExported($user, $data));

            config(['queue.default' => 'sqs']);
        }
    }

}

