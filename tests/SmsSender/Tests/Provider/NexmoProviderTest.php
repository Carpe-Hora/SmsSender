<?php

namespace SmsSender\Tests\Provider;

use SmsSender\HttpAdapter\HttpAdapterInterface;
use SmsSender\Provider\NexmoProvider;
use SmsSender\Result\ResultInterface;
use SmsSender\Tests\TestCase;

class NexmoProviderTest extends TestCase
{
    /**
     * @expectedException \RuntimeException
     */
    public function testSendWithNullApiCredentials()
    {
        $adapter = $this->getMock('\SmsSender\HttpAdapter\HttpAdapterInterface');
        $provider = new NexmoProvider($adapter, null, null);
        $provider->send('0642424242', 'foo!');
    }

    /**
     * @expectedException \RuntimeException
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
     * @expectedException \RuntimeException
     */
    public function testSendWithNullPhone()
    {
        $adapter = $this->getMock('\SmsSender\HttpAdapter\HttpAdapterInterface');
        $this->provider = new NexmoProvider($adapter, 'key', 'secret');
        $result = $this->provider->send(null, 'foo', 'originator');
    }

    public function testSendWithNullMessage()
    {
        $this->provider = new NexmoProvider($this->getMockAdapter(), 'key', 'secret');
        $result = $this->provider->send('0642424242', null, 'originator');

        $this->assertNull($result['id']);
        $this->assertEquals(ResultInterface::STATUS_FAILED, $result['status']);
        $this->assertEquals('0642424242', $result['recipient']);
        $this->assertNull($result['body']);
        $this->assertEquals('originator', $result['originator']);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testSendWithEmptyPhone()
    {
        $adapter = $this->getMock('\SmsSender\HttpAdapter\HttpAdapterInterface');
        $this->provider = new NexmoProvider($adapter, 'key', 'secret');
        $result = $this->provider->send('', 'foo', 'originator');
    }

    public function testSendWithEmptyMessage()
    {
        $this->provider = new NexmoProvider($this->getMockAdapter(), 'key', 'secret');
        $result = $this->provider->send('0642424242', '', 'originator' );

        $this->assertNull($result['id']);
        $this->assertEquals(ResultInterface::STATUS_FAILED, $result['status']);
        $this->assertEquals('0642424242', $result['recipient']);
        $this->assertEquals('', $result['body']);
        $this->assertEquals('originator', $result['originator']);
    }

    public function testSendWithLocalPhoneNumber()
    {
        $adapter = new MockAdapter;
        $this->provider = new NexmoProvider($adapter, 'key', 'secret');
        $result = $this->provider->send('0642424242', 'foo', 'originator');

        $this->assertEquals('+33642424242', $adapter->data['to']);
    }

    public function testSendWithUnicodeMessage()
    {
        $adapter = new MockAdapter;
        $this->provider = new NexmoProvider($adapter, 'key', 'secret');
        $result = $this->provider->send('0642424242', 'foo €', 'originator');

        $this->assertEquals('unicode', $adapter->data['type']);
    }

    public function testSendWithTextMessage()
    {
        $adapter = new MockAdapter;
        $this->provider = new NexmoProvider($adapter, 'key', 'secret');
        $result = $this->provider->send('0642424242', 'foo', 'originator');

        $this->assertEquals('text', $adapter->data['type']);
    }
}

class MockAdapter implements HttpAdapterInterface
{
    public $data;

    public function getContent($url, $method = 'GET', array $headers = array(), array $data = array())
    {
      $this->data = $data;
      return json_encode($data);
    }

    public function getName()
    {
      return 'MockAdapter';
    }
}