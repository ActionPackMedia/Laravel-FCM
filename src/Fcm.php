<?php

namespace Edujugon\PushNotification;

use Carbon\Carbon;
use Edujugon\PushNotification\Exceptions\PushNotificationException;
use Google\Client as GoogleClient;
use Google\Service\FirebaseCloudMessaging;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Fcm extends PushService implements Contracts\PushServiceInterface
{
    const CACHE_SECONDS = 55 * 60; // 55 minutes

    /**
     * Number of concurrent requests to multiplex in the same connection.
     *
     * @var int
     */
    protected $concurrentRequests = 10;

    protected $unregisteredDeviceTokens = [];

    protected $feedbacks = [];
    /**
     * @var Client
     */
    private $client;
    
    /**
     * Fcm constructor.
     * Override parent constructor.
     * @throws PushNotificationException
     */
    public function __construct()
    {
        $this->config = $this->initializeConfig('fcm');

        $this->url = 'https://fcm.googleapis.com/v1/projects/' . $this->config['projectId'] . '/messages:send';

        $this->client = new Client($this->config['guzzle'] ?? []);

        $this->concurrentRequests = $this->config['concurrentRequests'] ?? 10;
    }

    /**
     * Set the projectId for the notification
     * @param string $projectId
     */
    public function setProjectId(string $projectId)
    {
        $this->config['projectId'] = $projectId;

        $this->url = 'https://fcm.googleapis.com/v1/projects/' . $this->config['projectId'] . '/messages:send';
    }

    /**
     * Set the jsonFile path for the notification
     * @param string $jsonFile
     */
    public function setJsonFile(string $jsonFile)
    {
        $this->config['jsonFile'] = $jsonFile;
    }

    /**
     * Update the values by key on config array from the passed array. If any key doesn't exist, it's added.
     * @param array $config
     */
    public function setConfig(array $config)
    {
        parent::setConfig($config);

        // Update url
        $this->setProjectId($this->config['projectId']);
    }

    /**
     * Set the needed headers for the push notification.
     *
     * @return array
     */
    protected function addRequestHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->getOauthToken(),
            'Content-Type' =>  'application/json',
        ];
    }

    /**
     * Send Push Notification
     *
     * @param  array $deviceTokens
     * @param array $message
     *
     */
    public function send(array $deviceTokens, array $message): void
    {
        // FCM v1 does not allows multiple devices at once
        $headers = $this->addRequestHeaders();
        $jsonData = ['message' => $this->buildMessage($message)];

        $this->feedbacks = [];
        $this->unregisteredDeviceTokens = [];

        $requests = [];
        
        foreach ($deviceTokens as $deviceToken) {
            $jsonData['message']['token'] = $deviceToken;

            $body = json_encode($jsonData);

            $requests[$deviceToken] = new Request('POST', $this->url, $headers, $body);
        }

        $pool = new Pool($this->client, $requests, [
            'concurrency' => $this->concurrentRequests,
            'fulfilled' => function (GuzzleResponse $response, $deviceToken) {
                // this is delivered each successful response

                $this->feedbacks[$deviceToken] = [
                    'success' => true,
                    'response' => json_decode((string) $response->getBody(), true, 512, JSON_BIGINT_AS_STRING),
                ];
            },
            'rejected' => function (RequestException $reason, $deviceToken) {
                // this is delivered each failed request

                $error = json_decode((string) $reason->getResponse()->getBody(), true);

                $this->feedbacks[$deviceToken] = [
                    'success' => false,
                    'error' => $error,
                ];

                if (isset($error['error']['code']) && $error['error']['code'] == 404) {
                    $this->unregisteredDeviceTokens[] = $deviceToken;
                }
            },
        ]);

        // Initiate the transfers and create a promise
        $promise = $pool->promise();

        // Force the pool of requests to complete.
        $promise->wait();

        $this->setFeedback((object)$this->feedbacks);
    }

    /**
     * Provide the unregistered tokens of the notification.
     *
     * @param array $devices_token
     * @return array $tokenUnRegistered
     */
    public function getUnregisteredDeviceTokens(array $devices_token): array
    {
        return $this->unregisteredDeviceTokens;
    }

    /**
     * Prepare the data to be sent
     *
     * @param $topic
     * @param $message
     * @param $isCondition
     * @return array
     */
    protected function buildData($topic, $message, $isCondition): array
    {
        $condition = $isCondition ? ['condition' => $topic] : ['to' => '/topics/' . $topic];

        return [
            'message' => array_merge($condition, $this->buildMessage($message)),
        ];
    }
    
    /**
     * @param $message
     * @return array
     */
    protected function buildMessage($message): array
    {
        // if no notification nor data keys, then set Data Message as default.
        if (!array_key_exists('data', $message) && !array_key_exists('notification', $message)) {
            return ['data' => $message];
        }
        
        return $message;
    }

    protected function getOauthToken()
    {
        return Cache::remember(
            Str::slug('fcm-v1-oauth-token-' . $this->config['projectId']),
            Carbon::now()->addSeconds(self::CACHE_SECONDS),
            function () {
                $jsonFilePath = $this->config['jsonFile'];

                $googleClient = new GoogleClient();

                $googleClient->setAuthConfig($jsonFilePath);
                $googleClient->addScope(FirebaseCloudMessaging::FIREBASE_MESSAGING);

                $accessToken = $googleClient->fetchAccessTokenWithAssertion();

                $oauthToken = $accessToken['access_token'];

                return $oauthToken;
            }
        );
    }
}
