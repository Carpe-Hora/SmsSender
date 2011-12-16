<?php

namespace chSmsSender\Tests\Provider;

use chSmsSender\Tests\TestCase;

use chSmsSender\Provider\EsendexProvider;

class EsendexProviderTest extends TestCase
{
    /**
     * @expectedException \RuntimeException
     */
    public function testSendWithNullApiCredentials()
    {
        $adapter = $this->getMock('\chSmsSender\HttpAdapter\HttpAdapterInterface');
        $provider = new EsendexProvider($adapter, null, null, null);
        $provider->send('0642424242', 'foo!');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetStatusWithNullApiCredentials()
    {
        $adapter = $this->getMock('\chSmsSender\HttpAdapter\HttpAdapterInterface');
        $provider = new EsendexProvider($adapter, null, null, null);
        $provider->getStatus('dummyMessageId');
    }

    public function testSend()
    {
        $this->provider = new EsendexProvider($this->getMockAdapter(), 'username', 'pass', 'account');
        $result = $this->provider->send('0642424242', 'foo');

        $this->assertNull($result['id']);
        $this->assertNull($result['sent']);
    }

    public function testSendWithMockData()
    {
        $data = <<<EOF
Result=OK
MessageIDs=3c13bbba-a9c2-460c-961b-4d6772960af0
EOF;
        $this->provider = new EsendexProvider($this->getMockAdapter(null, $data), 'username', 'pass', 'account');
        $result = $this->provider->send('0642424242', 'foo');

        $this->assertEquals('3c13bbba-a9c2-460c-961b-4d6772960af0', $result['id']);
        $this->assertTrue($result['sent']);
    }

    public function testSendWithNullPhone()
    {
        $this->provider = new EsendexProvider($this->getMockAdapter(), 'username', 'pass', 'account');
        $result = $this->provider->send(null, 'foo');

        $this->assertNull($result['id']);
        $this->assertNull($result['sent']);
    }

    public function testSendWithNullMessage()
    {
        $this->provider = new EsendexProvider($this->getMockAdapter(), 'username', 'pass', 'account');
        $result = $this->provider->send('0642424242', null);

        $this->assertNull($result['id']);
        $this->assertNull($result['sent']);
    }

    public function testSendWithEmptyPhone()
    {
        $this->provider = new EsendexProvider($this->getMockAdapter(), 'username', 'pass', 'account');
        $result = $this->provider->send('', 'foo');

        $this->assertNull($result['id']);
        $this->assertNull($result['sent']);
    }

    public function testSendWithEmptyMessage()
    {
        $this->provider = new EsendexProvider($this->getMockAdapter(), 'username', 'pass', 'account');
        $result = $this->provider->send('0642424242', '');

        $this->assertNull($result['id']);
        $this->assertNull($result['sent']);
    }
/*
    public function testSendForReal()
    {
        if (!isset($_SERVER['ESENDEX_API_USER']) || !isset($_SERVER['ESENDEX_API_PASS']) || !isset($_SERVER['ESENDEX_API_ACCOUNT'])) {
            $this->markTestSkipped('You need to configure the ESENDEX_API_USER, ESENDEX_API_PASS, ESENDEX_API_ACCOUNT values in phpunit.xml');
        }

        $this->provider = new EsendexProvider($this->getMockAdapter(), $_SERVER['ESENDEX_API_USER'], $_SERVER['ESENDEX_API_PASS'], $_SERVER['ESENDEX_API_ACCOUNT']);
        $result = $this->provider->send('0642424242', ''); // @todo: get a fake number

        $this->assertEquals('foo', $result['id']);
        $this->assertTrue($result['sent']);
    }
*/
}
