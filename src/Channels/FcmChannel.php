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
        /**
         * https://firebase.google.com/docs/reference/fcm/rest/v1/projects.messages?hl=en
         * todo can add separate options for android and ios
         */
        return [
            'notification' => [
                'title' => $message->title,
                'body' => $message->body,
            ],
            'data' => $message->extra,
        ];
    }
}
