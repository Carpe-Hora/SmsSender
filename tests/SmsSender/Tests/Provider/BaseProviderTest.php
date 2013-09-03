<?php

namespace SmsSender\Tests\Provider;

use SmsSender\Result\ResultInterface;
use SmsSender\Tests\TestCase;

/**
 * All provider related tests must inherit from this class.
 *
 * Some behavior are shared between all providers, this base test class tests
 * these behaviors.
 *
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
abstract class BaseProviderTest extends TestCase
{
    abstract protected function getProvider($adapter);


    /**
     * @dataProvider invalidBodyProvider
     */
    public function testSendWithInvalidBody($message)
    {
        $provider = $this->getProvider($this->getMockAdapter());
        $result = $provider->send('0642424242', $message, 'originator');

        $this->assertNull($result['id']);
        $this->assertEquals(ResultInterface::STATUS_FAILED, $result['status']);
        $this->assertEquals('0642424242', $result['recipient']);
        $this->assertSame($message, $result['body']);
        $this->assertEquals('originator', $result['originator']);
    }

    public function invalidBodyProvider()
    {
        return array(
            array(null),
            array(''),
        );
    }
}
