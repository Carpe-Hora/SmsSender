<?php

namespace SmsSender\Tests;

use SmsSender\SmsSender;
use SmsSender\Provider\ProviderInterface;

/**
 * @author Kévin Gomez <kevin_gomez@carpe-hora.com>
 */
class SmsSenderTest extends TestCase
{
    protected $sender;

    protected function setUp()
    {
        $this->sender = new TestableSmsSender();
    }

    public function testRegisterProvider()
    {
        $provider = new MockProvider('test');
        $this->sender->registerProvider($provider);

        $this->assertSame($provider, $this->sender->getProvider());
    }

    public function testRegisterProviders()
    {
        $provider = new MockProvider('test');
        $this->sender->registerProviders(array($provider));

        $this->assertSame($provider, $this->sender->getProvider());
    }

    public function testUsing()
    {
        $provider1 = new MockProvider('test1');
        $provider2 = new MockProvider('test2');
        $this->sender->registerProviders(array($provider1, $provider2));

        $this->assertSame($provider1, $this->sender->getProvider());

        $this->sender->using('test1');
        $this->assertSame($provider1, $this->sender->getProvider());

        $this->sender->using('test2');
        $this->assertSame($provider2, $this->sender->getProvider());

        $this->sender->using('test1');
        $this->assertSame($provider1, $this->sender->getProvider());

        $this->sender->using('non_existant');
        $this->assertSame($provider1, $this->sender->getProvider());

        $this->sender->using(null);
        $this->assertSame($provider1, $this->sender->getProvider());

        $this->sender->using('');
        $this->assertSame($provider1, $this->sender->getProvider());
    }

    public function testGetProviders()
    {
        $provider1 = new MockProvider('test1');
        $provider2 = new MockProvider('test2');

        $this->sender->registerProviders(array($provider1, $provider2));
        $result = $this->sender->getProviders();

        $expected = array(
            'test1' => $provider1,
            'test2' => $provider2
        );

        $this->assertSame($expected, $result);
        $this->assertArrayHasKey('test1', $result);
        $this->assertArrayHasKey('test2', $result);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetProvider()
    {
        $this->sender->getProvider();
        $this->fail('getProvider() should throw an exception');
    }

    public function testGetProviderWithMultipleProvidersReturnsTheFirstOne()
    {
        $provider1 = new MockProvider('test1');
        $provider2 = new MockProvider('test2');
        $provider3 = new MockProvider('test3');
        $this->sender->registerProviders(array($provider1, $provider2, $provider3));

        $this->assertSame($provider1, $this->sender->getProvider());
    }

    public function testSendReturnsInstanceOfSms()
    {
        $this->sender->registerProvider(new MockProvider('test1'));
        $this->assertInstanceOf('\SmsSender\Result\Sms', $this->sender->send('phone', 'message'));
    }

    public function testEmpty()
    {
        $this->sender->registerProvider(new MockProviderWithRequestCount('test2'));
        $this->assertEmptyResult($this->sender->send('', 'foo'));
        $this->assertEquals(0, $this->sender->getProvider('test2')->sendCount);
        $this->assertEmptyResult($this->sender->send('0565212547', ''));
        $this->assertEquals(0, $this->sender->getProvider('test2')->sendCount);
        $this->assertEmptyResult($this->sender->send('0565212547', 'foo'));
        $this->assertEquals(1, $this->sender->getProvider('test2')->sendCount);
    }

    protected function assertEmptyResult($result)
    {
        $this->assertNull($result->getId());
        $this->assertNull($result->isSent());
    }
}

class MockProvider implements ProviderInterface
{
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function send($recipient, $body, $originator = '')
    {
        return array();
    }

    public function getName()
    {
        return $this->name;
    }
}

class MockProviderWithRequestCount extends MockProvider
{
    public $sendCount = 0;

    public function send($number, $message, $originator = '')
    {
        $this->sendCount++;
        return array();
    }
}

class TestableSmsSender extends SmsSender
{
    public $countCallGetProvider = 0;

    public function getProvider()
    {
        $this->countCallGetProvider++;

        return parent::getProvider();
    }
}
