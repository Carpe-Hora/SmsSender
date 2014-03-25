<?php

namespace SmsSender\Tests\Provider;

use SmsSender\Provider\NexmoProvider;
use SmsSender\Result\ResultInterface;

class NexmoProviderTest extends BaseProviderTest
{
    protected function getProvider($adapter)
    {
        return new NexmoProvider($adapter, 'key', 'secret');
    }

    /**
     * @expectedException           \RuntimeException
     * @expectedExceptionMessage    No API credentials provided
     */
    public function testSendWithNullApiCredentials()
    {
        $adapter = $this->getMock('\SmsSender\HttpAdapter\HttpAdapterInterface');
        $provider = new NexmoProvider($adapter, null, null);
        $provider->send('0642424242', 'foo!');
    }

    /**
     * @expectedException           \RuntimeException
     * @expectedExceptionMessage    The originator parameter is required for this provider.
     */
    public function testSendWithNoOriginator()
    {
        $adapter = $this->getMock('\SmsSender\HttpAdapter\HttpAdapterInterface');
        $provider = new NexmoProvider($adapter, 'key', 'secret');
        $provider->send('0642424242', 'foo!');
    }

    public function testSend()
    {
        $this->provider = new NexmoProvider($this->getMockAdapter(), 'key', 'secret');
        $result = $this->provider->send('0642424242', 'foo', 'originator');

        $this->assertNull($result['id']);
        $this->assertEquals(ResultInterface::STATUS_FAILED, $result['status']);
        $this->assertEquals('0642424242', $result['recipient']);
        $this->assertEquals('foo', $result['body']);
        $this->assertEquals('originator', $result['originator']);
    }

    public function testSendWithMockData()
    {
        $data = <<<EOF
{"message-count":"1","messages":[{"to":"33698568827","message-price":"0.04500000","status":"0","message-id":"0A130A1B","remaining-balance":"1.81000000"}]}
EOF;
        $this->provider = new NexmoProvider($this->getMockAdapter(null, $data), 'key', 'secret');
        $result = $this->provider->send('0642424242', 'foo', 'originator');

        $this->assertEquals('0A130A1B', $result['id']);
        $this->assertEquals(ResultInterface::STATUS_SENT, $result['status']);
        $this->assertEquals('0642424242', $result['recipient']);
        $this->assertEquals('foo', $result['body']);
        $this->assertEquals('originator', $result['originator']);
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
                    return !empty($data['to']) && $data['to'] === $expectedRecipient;
                })
            );

        // setup the provider
        if ($internationalPrefix === null) {
            $provider = new NexmoProvider($adapter, 'key', 'secret');
        } else {
            $provider = new NexmoProvider($adapter, 'key', 'secret', $internationalPrefix);
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
     * @dataProvider validBodyProvider
     */
    public function testSendDetectsBodyType($body, $expectedType)
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
                $this->callback(function ($data) use ($expectedType) {
                    return !empty($data['type']) && $data['type'] === $expectedType;
                })
            );

        // setup the provider
        $provider = new NexmoProvider($adapter, 'key', 'secret');

        // launch the test
        $provider->send('0642424242', $body, 'originator');
    }

    public function validBodyProvider()
    {
        return array(
            array('foo', 'text'),
            array('foo €', 'unicode'),
        );
    }
}
