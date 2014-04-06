<?php

namespace SmsSender\Tests\Provider;

use SmsSender\HttpAdapter\HttpAdapterInterface;
use SmsSender\Provider\TwilioProvider;
use SmsSender\Result\ResultInterface;

class TwilioProviderTest extends BaseProviderTest
{
    protected function getProvider($adapter)
    {
        return new TwilioProvider($adapter, 'key', 'secret');
    }

    /**
     * @expectedException           \RuntimeException
     * @expectedExceptionMessage    No API credentials provided
     */
    public function testSendWithNullApiCredentials()
    {
        $adapter = $this->getMock('\SmsSender\HttpAdapter\HttpAdapterInterface');
        $provider = new TwilioProvider($adapter, null, null);
        $provider->send('0642424242', 'foo!');
    }

    /**
     * @expectedException           \BadMethodCallException
     * @expectedExceptionMessage    The originator parameter is required for this provider.
     */
    public function testSendWithNoOriginator()
    {
        $provider = new TwilioProvider($this->getMockAdapter($this->never()), 'key', 'secret');
        $provider->send('0642424242', 'foo!');
    }

    public function testSend()
    {
        $provider = $this->getProvider($this->getMockAdapter());
        $result = $provider->send('0642424242', 'foo', 'originator');

        $this->assertNull($result['id']);
        $this->assertEquals(ResultInterface::STATUS_FAILED, $result['status']);
        $this->assertEquals('0642424242', $result['recipient']);
        $this->assertEquals('foo', $result['body']);
        $this->assertEquals('originator', $result['originator']);
    }

    public function testSendWithMockData()
    {
        $data = <<<EOF
{"sid":"SMfb01b1397d9b252ddd078351db17a814","date_created":"Mon, 02 Sep 2013 14:38:21 +0000","date_updated":"Mon, 02 Sep 2013 14:38:21 +0000","date_sent":null,"account_sid":"ACe2df2c142152c20224fbf059be28e7d7","to":"+642424242","from":"+15005550006","body":"foo","status":"queued","direction":"outbound-api","api_version":"2010-04-01","price":null,"price_unit":"USD","uri":"\/2010-04-01\/Accounts\/ACe2df2c142152c20224fbf059be28e7d7\/SMS\/Messages\/SMfb01b1397d9b252ddd078351db17a814.json"}
EOF;
        $provider = $this->getProvider($this->getMockAdapter(null, $data));
        $result = $provider->send('0642424242', 'foo', '+15005550006');

        $this->assertEquals('SMfb01b1397d9b252ddd078351db17a814', $result['id']);
        $this->assertEquals(ResultInterface::STATUS_SENT, $result['status']);
        $this->assertEquals('0642424242', $result['recipient']);
        $this->assertEquals('foo', $result['body']);
        $this->assertEquals('+15005550006', $result['originator']);
    }

    /**
     * @dataProvider validRecipientProvider
     */
    public function testSendCleansRecipientNumber($recipient, $expectedRecipient, $internationalPrefix = null)
    {
        // setup the adapter
        $adapter = $this->getMock('\SmsSender\HttpAdapter\HttpAdapterInterface');
        $adapter
            ->expects($this->once())
            ->method('getContent')
            ->with(
                $this->anything(),      // URL
                $this->equalTo('POST'), // method
                $this->anything(),      // headers
                $this->callback(function ($data) use ($expectedRecipient) {
                    return !empty($data['To']) && $data['To'] === $expectedRecipient;
                })
            );

        // setup the provider
        if ($internationalPrefix === null) {
            $provider = new TwilioProvider($adapter, 'key', 'secret');
        } else {
            $provider = new TwilioProvider($adapter, 'key', 'secret', $internationalPrefix);
        }

        // launch the test
        $provider->send($recipient, 'foo', 'originator');
    }

    public function validRecipientProvider()
    {
        return array(
            array('0642424242', '+33642424242', null),
            array('0642424242', '+33642424242', '+33'),
            array('0642424242', '+44642424242', '+44'),
            array('+33642424242', '+33642424242', '+33'),
            array('+33642424242', '+33642424242', '+44'),
        );
    }

    /**
     * @requires extension curl
     */
    public function testRealSend()
    {
        if (empty($_SERVER['TWILIO_ACCOUNT_SID']) || empty($_SERVER['TWILIO_API_SECRET'])) {
            $this->markTestSkipped('No test credentials configured.');
        }

        $adapter = new \SmsSender\HttpAdapter\CurlHttpAdapter();
        $provider = new TwilioProvider($adapter, $_SERVER['TWILIO_ACCOUNT_SID'], $_SERVER['TWILIO_API_SECRET']);
        $sender = new \SmsSender\SmsSender($provider);
        $result = $sender->send('0642424242', 'foo', '+15005550006');

        $this->assertTrue(!empty($result['id']));
        $this->assertEquals(ResultInterface::STATUS_SENT, $result['status']);
        $this->assertEquals('0642424242', $result['recipient']);
        $this->assertEquals('foo', $result['body']);
        $this->assertEquals('+15005550006', $result['originator']);
    }
}
