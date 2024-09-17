<?php


use PHPUnit\Framework\TestCase;
use Edujugon\PushNotification\PushNotification;
use Illuminate\Support\Arr;

class PushNotificationTest extends TestCase {

    /** @test */
    public function push_notification_instance_creation_without_argument_set_gcm_as_service()
    {
        $push = new PushNotification();

        $this->assertInstanceOf('Edujugon\PushNotification\Gcm', $push->service);
    }

    /** @test */
    public function assert_send_method_returns_an_stdClass_instance()
    {
        $push = new PushNotification();

        $push->setMessage(['message'=>'Hello World'])
                ->setDevicesToken(['howoPaqCPp1pvVsBZ6QUHoEtO_S9-Esel4N7nqeUypQ6ah8MKZKo6jl'])
                ->setConfig(['dry_run' => true]);

        $push = $push->send();

        $this->assertInstanceOf('stdClass', $push->getFeedback());

    }
    /** @test */
    public function assert_there_is_an_array_key_called_error()
    {
        $push = new PushNotification();

        $push->setMessage(['message'=>'Hello World'])
            ->setDevicesToken(['d1WaXouhHG34:AaPA91bF2byCOq-gexmHFqdysYX'])
            ->setConfig(['dry_run' => true])
            ->send();

        $this->assertTrue(isset($push->feedback->error));
    }

    /** @test */
    public function assert_unregistered_device_tokens_is_an_array()
    {
        $push = new PushNotification();

        $push
            ->setDevicesToken([
                'asdfasdfasdfasdfXCXQ9cvvpLMuxkaJ0ySpWPed3cvz0q4fuG1SXt40-oasdf3nhWE5OKDmatFZaaZ',
                'asfasdfasdf_96ssdfsWuhabpZO9Basvz0q4fuG1SXt40-oXH4R5dwYk4rQYTeds3nhWE5OKDmatFZaaZ'
            ])
            ->setConfig(['dry_run' => true])
            ->setMessage(['message' =>'hello world'])
            ->send();

        $this->assertIsArray($push->getUnregisteredDeviceTokens());
    }

//    /** @test */
//    public function set_and_get_service_config()
//    {
//
//        /** GCM */
//        $push = new PushNotification();
//
//        $push->setConfig(['time_to_live' => 3]);
//
//        $this->assertArrayHasKey('time_to_live', $push->config);
//        $this->assertArrayHasKey('priority', $push->config); //default key
//        $this->assertIsArray($push->config);
//
//        /** APNS */
//        $pushAPN = new PushNotification('apn');
//
//        $pushAPN->setConfig(['time_to_live' => 3]);
//
//        $this->assertArrayHasKey('time_to_live', $pushAPN->config);
//        $this->assertArrayHasKey('certificate', $pushAPN->config); //default key
//        $this->assertIsArray($pushAPN->config);
//    }

    /** @test */
    public function set_message_data()
    {
        $push = new PushNotification();

        $push->setMessage(['message' =>'hello world']);

        $this->assertArrayHasKey('message', $push->message);

        $this->assertEquals('hello world', $push->message['message']);
    }

    /** @test */
    public function fcm_assert_send_method_returns_an_stdClass_instance()
    {
        $push = new PushNotification('fcm');

        $push->setMessage(['message'=>'Hello World'])
            ->setDevicesToken(['asdfasefaefwefwerwerwer'])
            ->setConfig(['dry_run' => false]);

        $push = $push->send();

        $this->assertEquals('https://fcm.googleapis.com/fcm/send', $push->url);
        $this->assertInstanceOf('stdClass', $push->getFeedback());
    }

    /** @test */
    public function if_push_service_as_argument_is_not_valid_user_gcm_as_default()
    {
        $push = new PushNotification('asdf');

        $this->assertInstanceOf('Edujugon\PushNotification\Gcm', $push->service);
    }

    /** @test */
    public function get_available_push_service_list()
    {
        $push = new PushNotification();

        $this->assertCount(3, $push->servicesList);
        $this->assertIsArray($push->servicesList);
    }

    /** @test */
    public function if_argument_in_set_service_method_does_not_exist_set_the_service_by_default(){
        $push = new PushNotification();

        $push->setService('asdf')->send();
        $this->assertInstanceOf('Edujugon\PushNotification\Gcm', $push->service);

        $push->setService('fcm');
        $this->assertInstanceOf('Edujugon\PushNotification\Fcm', $push->service);
    }

    /** @test */
    public function get_feedback_after_sending_a_notification()
    {
        $push = new PushNotification('fcm');

        $response = $push->setMessage(['message'=>'Hello World'])
            ->setDevicesToken(['asdfasefaefwefwerwerwer'])
            ->setConfig(['dry_run' => false])
            ->send()
            ->getFeedback();

        $this->assertInstanceOf('stdClass', $response);
    }

    public function send_a_notification_by_topic_in_fcm()
    {
        $push = new PushNotification('fcm');

        $response = $push->setMessage(['message'=>'Hello World'])
            ->setConfig(['dry_run' => false])
            ->sendByTopic('test')
            ->getFeedback();

        $this->assertInstanceOf('stdClass', $response);
    }

    /** @test */
    public function send_a_notification_by_condition_in_fcm()
    {
        $push = new PushNotification('fcm');

        $response = $push->setMessage(['message'=>'Hello World'])
            ->setConfig(['dry_run' => false])
            ->sendByTopic("'dogs' in topics || 'cats' in topics", true)
            ->getFeedback();

        $this->assertInstanceOf('stdClass', $response);
    }
}
