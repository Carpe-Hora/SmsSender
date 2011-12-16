<?php

namespace chSmsSender\Tests\Provider;

use chSmsSender\Tests\TestCase;

use chSmsSender\HttpAdapter\HttpAdapterInterface;
use chSmsSender\Provider\AbstractProvider;

/**
 * @author Kévin Gomez <kevin_gomez@carpe-hora.com>
 */
class AbstractProviderTest extends TestCase
{
    public function testGetAdapter()
    {
        $adapter  = new MockHttpAdapter();
        $provider = new MockProvider($adapter);

        $this->assertSame($adapter, $provider->getAdapter());
    }

    public function testGetDefaults()
    {
        $adapter  = new MockHttpAdapter();
        $provider = new MockProvider($adapter);
        $result   = $provider->getDefaults();

        $this->assertEquals(2, count($result));
        $this->assertNull($result['id']);
        $this->assertNull($result['sent']);
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
}

class MockHttpAdapter implements HttpAdapterInterface
{
    public function getContent($url, $method = 'GET', array $headers = array(), array $data = array())
    {
    }

    public function getName()
    {
        return 'mock_http_adapter';
    }
}
