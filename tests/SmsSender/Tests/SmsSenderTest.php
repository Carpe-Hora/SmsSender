<?php

namespace SmsSender\Tests;

use SmsSender\SmsSender;

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
        $provider = $this->getTestProvider('test_provider');

        $this->sender->registerProvider($provider);
        $this->assertSame($provider, $this->sender->getProvider());
    }

    public function testRegisterProviders()
    {
        $provider = $this->getTestProvider('test_provider');

        $this->sender->registerProviders(array($provider));
        $this->assertSame($provider, $this->sender->getProvider());
    }

    public function testUsing()
    {
        $provider1 = $this->getTestProvider('test1');
        $provider2 = $this->getTestProvider('test2');
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
        $provider1 = $this->getTestProvider('test1');
        $provider2 = $this->getTestProvider('test2');

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
        $provider1 = $this->getTestProvider('test1');
        $provider2 = $this->getTestProvider('test2');
        $this->sender->registerProviders(array($provider1, $provider2));

        $this->assertSame($provider1, $this->sender->getProvider());
    }

    public function testSendReturnsInstanceOfSms()
    {
        $number = 'phone number';
        $message = 'message content';

        $provider = $this->getTestProvider('dummy');
        $provider
            ->expects($this->once())
            ->method('send')
            ->with($number, $message)
            ->will($this->returnValue(array()));

        $this->sender->registerProvider($provider);
        $this->assertInstanceOf('\SmsSender\Result\Sms', $this->sender->send($number, $message));
    }

    public function testEmpty()
    {
        $number = 'phone number';
        $message = 'message content';

        $provider = $this->getTestProvider('dummy');
        $provider
            ->expects($this->once())
            ->method('send')
            ->with($number, $message)
            ->will($this->returnValue(array()));

        $this->sender->registerProvider($provider);
        $this->assertEmptyResult($this->sender->send('', $message));
        $this->assertEmptyResult($this->sender->send($number, ''));
        $this->assertEmptyResult($this->sender->send($number, $message));
    }

    protected function assertEmptyResult($result)
    {
        $this->assertInstanceOf('\SmsSender\Result\Sms', $result);

        $this->assertNull($result->getId());
        $this->assertFalse($result->isSent());
    }

    protected function getTestProvider($name)
    {
        $provider = $this->getMock('\SmsSender\Provider\ProviderInterface');
        $provider
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($name));

        return $provider;
    }
}

class TestableSmsSender extends SmsSender
{
    public function getProvider()
    {
        return parent::getProvider();
    }
}
