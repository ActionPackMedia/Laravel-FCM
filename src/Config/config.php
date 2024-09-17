<?php
/**
 * @see https://github.com/Edujugon/PushNotification
 */

return [
    'fcm' => [
        'priority' => 'normal',
        'dry_run' => false,
        'projectId' => 'my-project-id',
        'jsonFile' => __DIR__ . '/fcmCertificates/file.json',
        // 'concurrentRequests' => 5, // Optional, default 10
        // Optional: Default Guzzle request options for each FCM request
        // See https://docs.guzzlephp.org/en/stable/request-options.html
        'guzzle' => [],
    ],
];
