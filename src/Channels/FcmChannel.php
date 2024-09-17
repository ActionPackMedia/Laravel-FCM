<?php

namespace Edujugon\PushNotification\Channels;

use Edujugon\PushNotification\Messages\PushMessage;

class FcmChannel extends GcmChannel
{
    /**
     * {@inheritdoc}
     */
    protected function pushServiceName()
    {
        return 'fcm';
    }
    
    protected function buildData(PushMessage $message)
    {
        return [
            'notification' => [
                'title' => $message->title,
                'body' => $message->body,
            ],
            'data' => $message->extra,
        ];
    }
}
