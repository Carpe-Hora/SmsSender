<?php

namespace SmsSender\Tests\Provider;

use SmsSender\Provider\TwilioProvider;
use SmsSender\Provider\WebsmsProvider;
use SmsSender\Result\ResultInterface;

class WebsmsProviderTest extends BaseProviderTest
{
    protected function getProvider($adapter)
    {
        return new WebsmsProvider($adapter, 'access_token');
    }

    /**
     * @expectedException           \SmsSender\Exception\InvalidCredentialsException
     * @expectedExceptionMessage    No API credentials provided
     */
    public function testSendWithNullApiCredentials()
    {
        $adapter = $this->getMock('\SmsSender\HttpAdapter\HttpAdapterInterface');
        $provider = new WebsmsProvider($adapter, null, null);
        $provider->send('066412345678', 'foo!');
    }

    public function testSend()
    {
        $provider = $this->getProvider($this->getMockAdapter());
        $result = $provider->send('066412345678', 'foo', 'originator');

        $this->assertNull($result['id']);
        $this->assertEquals(ResultInterface::STATUS_FAILED, $result['status']);
        $this->assertEquals('066412345678', $result['recipient']);
        $this->assertEquals('foo', $result['body']);
        $this->assertEquals('originator', $result['originator']);
    }

    public function testSendWithMockData()
    {
        $data = <<<EOF
{"statusCode":2000,"statusMessage":"OK","transferId":"005440da3e00078f5214"}
EOF;
        $provider = $this->getProvider($this->getMockAdapter(null, $data));
        $result = $provider->send('066412345678', 'foo');

        $this->assertEquals('005440da3e00078f5214', $result['id']);
        $this->assertEquals(ResultInterface::STATUS_SENT, $result['status']);
        $this->assertEquals('066412345678', $result['recipient']);
        $this->assertEquals('foo', $result['body']);
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
                    $dataArray = json_decode($data, true);

                    return is_array($dataArray['recipientAddressList'])
                        && count($dataArray['recipientAddressList']) > 0
                        && $dataArray['recipientAddressList'][0] === $expectedRecipient;
                })
            );

        // setup the provider
        if ($internationalPrefix === null) {
            $provider = new WebsmsProvider($adapter, 'access_token');
        } else {
            $provider = new WebsmsProvider($adapter, 'access_token', $internationalPrefix);
        }

        // launch the test
        $provider->send($recipient, 'foo');
    }

    public function validRecipientProvider()
    {
        return array(
            array('066412345678',   '4366412345678', null),
            array('066412345678',   '4366412345678', '+43'),
            array('066412345678',   '4466412345678', '+44'),
            array('+4366412345678', '4366412345678', '+43'),
            array('+4366412345678', '4366412345678', '+44'),
        );
    }

    /**
     * @requires extension curl
     */
    public function testRealSend()
    {
        if (empty($_SERVER['WEBSMS_ACCESS_TOKEN'])) {
            $this->markTestSkipped('No test credentials configured.');
        }

        $adapter = new \SmsSender\HttpAdapter\CurlHttpAdapter();
        $provider = new WebsmsProvider($adapter, $_SERVER['WEBSMS_ACCESS_TOKEN']);
        $sender = new \SmsSender\SmsSender($provider);
        $result = $sender->send('066412345678', 'foo');

        $this->assertTrue(!empty($result['id']));
        $this->assertEquals(ResultInterface::STATUS_SENT, $result['status']);
        $this->assertEquals('066412345678', $result['recipient']);
        $this->assertEquals('foo', $result['body']);
    }
}
