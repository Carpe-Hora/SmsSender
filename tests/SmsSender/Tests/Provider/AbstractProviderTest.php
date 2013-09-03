<?php

namespace SmsSender\Tests\Provider;

use SmsSender\Tests\TestCase;

use SmsSender\HttpAdapter\HttpAdapterInterface;
use SmsSender\Provider\AbstractProvider;
use SmsSender\Result\ResultInterface;

/**
 * @author Kévin Gomez <kevin_gomez@carpe-hora.com>
 */
class AbstractProviderTest extends TestCase
{
    public function testGetAdapter()
    {
        $adapter = $this->getMockAdapter($this->never());
        $provider = new MockProvider($adapter);

        $this->assertSame($adapter, $provider->getAdapter());
    }

    public function testGetDefaults()
    {
        $provider = new MockProvider($this->getMockAdapter($this->never()));
        $result   = $provider->getDefaults();

        $this->assertEquals(2, count($result));
        $this->assertNull($result['id']);
        $this->assertEquals(ResultInterface::STATUS_FAILED, $result['status']);
    }
}

class MockProvider extends AbstractProvider
{
    public function getAdapter()
    {
        return parent::getAdapter();
    }

    public function getDefaults()
    {
        return parent::getDefaults();
    }

    public function send($recipient, $body, $originator = '')
    {
    }

    public function getName()
    {
        return 'AbstractProviderTest';
    }
}
