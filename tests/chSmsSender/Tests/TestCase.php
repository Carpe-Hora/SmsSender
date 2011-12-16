<?php

namespace chSmsSender\Tests;

/**
 * @author Kévin Gomez <kevin_gomez@carpe-hora.com>
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @return \chSmsSender\HttpAdapter\HttpAdapterInterface
     */
    protected function getMockAdapter($expects = null, $content = null)
    {
        if (null === $expects) {
            $expects = $this->once();
        }

        $mock = $this->getMock('\chSmsSender\HttpAdapter\HttpAdapterInterface');
        $mock
            ->expects($expects)
            ->method('getContent')
            ->will(null !== $content ? $this->returnValue($content) : $this->returnArgument(0));

        return $mock;
    }
}
