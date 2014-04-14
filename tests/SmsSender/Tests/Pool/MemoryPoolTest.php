<?php

namespace SmsSender\Tests\Pool;

use SmsSender\Pool\MemoryPool;
use SmsSender\Result\Sms;
use SmsSender\Result\ResultInterface;
use SmsSender\Tests\TestCase;

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class MemoryPoolTest extends TestCase
{
    public function testMemoryPool()
    {
        // delayed messages
        $firstMessage = new Sms();
        $firstMessage->fromArray(array(
            'recipient' => '0642424242',
            'body'      => 'lala',
            'status'    => ResultInterface::STATUS_QUEUED,
        ));

        $secondMessage = new Sms();
        $secondMessage->fromArray(array(
            'recipient' => '0653535353',
            'body'      => 'lolo',
            'status'    => ResultInterface::STATUS_QUEUED,
        ));

        // sender mock
        $sender = $this->getMock('\SmsSender\SmsSenderInterface');
        $sender
            ->expects($this->at(0))
            ->method('send')
            ->with(
                $this->equalTo('0642424242'),
                $this->equalTo('lala'),
                $this->equalTo('')
            )
            ->will($this->returnValue('first'));
        $sender
            ->expects($this->at(1))
            ->method('send')
            ->with(
                $this->equalTo('0653535353'),
                $this->equalTo('lolo'),
                $this->equalTo('')
            )
            ->will($this->returnValue('second'));

        // tests
        $pool = new MemoryPool();
        $pool->enQueue($firstMessage);
        $pool->enQueue($secondMessage);

        // and flush it all!
        list($messages, $errors) = $pool->flush($sender);

        $this->assertSame(array('first', 'second'), $messages);
        $this->assertEmpty($errors);
    }
}
