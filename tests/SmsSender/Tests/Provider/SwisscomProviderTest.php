<?php

namespace SmsSender\Tests\Provider;

use SmsSender\Provider\SwisscomProvider;
use SmsSender\Tests\Provider\BaseProviderTest;
use SmsSender\Result\ResultInterface;

/**
 * Tests for SwisscomProvider, based on NexmioProvider tests
 */
class SwisscomProviderTest extends BaseProviderTest
{
    public function getProvider($adapter)
    {
        return new SwisscomProvider($adapter, 'clientId');
    }

    /**
     * @expectedException \SmsSender\Exception\InvalidCredentialsException
     * @expectedExceptionMessage No API credentials provided
     */
    public function testSendWithNullApiCredentials()
    {
        $adapter = $this->getMock('\SmsSender\HttpAdapter\HttpAdapterInterface');
        $provider = new SwisscomProvider($adapter, null);
        $provider->send('0642424242', 'foo!');
    }

    /**
     * @expectedException \SmsSender\Exception\InvalidArgumentException
     * @expectedExceptionMessage The originator parameter is required for this provider.
     */
    public function testSendWithNoOriginator()
    {
        $adapter = $this->getMock('\SmsSender\HttpAdapter\HttpAdapterInterface');
        $provider = $this->getProvider($adapter);
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
{
"outboundSMSMessageRequest": {
    "address": [
      "tel:+41originator"
    ],
    "deliveryInfoList": {
      "deliveryInfo": [
        {
          "address": "tel:+41642424242",
          "deliveryStatus": "DeliveredToNetwork"
        }
      ],
      "resourceURL": ""
    },
    "senderAddress": "tel:+41originator",
    "outboundSMSTextMessage": {
      "message": "foo"
    },
    "clientCorrelator": "0A130A1B",
    "senderName": "originator",
    "resourceURL": ""
  }
}
EOF;
        $provider = $this->getProvider($this->getMockAdapter(null, $data));
        $result = $provider->send('0642424242', 'foo', 'originator');

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
                $this->anything(), // URL
                $this->equalTo('POST'), // method
                $this->anything(), // headers
                $this->callback(function ($data) use ($expectedRecipient) {
                    $data = json_decode($data, true);
                    $data = explode(':', $data['outboundSMSMessageRequest']['address'][0]);
                    $to = array_pop($data);

                    return $to === $expectedRecipient;
                })
            );

        // setup the provider
        if ($internationalPrefix === null) {
            $provider = new SwisscomProvider($adapter, 'key');
        } else {
            $provider = new SwisscomProvider($adapter, 'key', $internationalPrefix);
        }

        // launch the test
        $provider->send($recipient, 'foo', 'originator');
    }

    public function validRecipientProvider()
    {
        return array(
            array('0642424242', '+41642424242', null),
            array('0642424242', '+33642424242', '+33'),
            array('0642424242', '+44642424242', '+44'),
            array('+33642424242', '+33642424242', '+33'),
            array('+33642424242', '+33642424242', '+44'),
        );
    }
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
