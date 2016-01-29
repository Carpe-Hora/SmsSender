<?php

namespace SmsSender\Tests;

use SmsSender\DelayedSender;
use SmsSender\Result\Sms;
use SmsSender\Result\ResultInterface;

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class DelayedSenderTest extends TestCase
{
    public function testSendIsDelayed()
    {
        $number = '0642424242';
        $body = 'lala';

        $delayedResult = new Sms();
        $delayedResult->fromArray(array(
            'recipient' => $number,
            'body'      => $body,
            'status'    => ResultInterface::STATUS_QUEUED,
        ));

        // pool mock
        $pool = $this->getMock('\SmsSender\Pool\PoolInterface');
        $pool
            ->expects($this->once())
            ->method('enQueue')
            ->with(
                $this->equalTo($delayedResult)
            );
        $pool
            ->expects($this->once())
            ->method('flush');

        // sender mock
        $sender = $this->getMock('\SmsSender\SmsSenderInterface');

        // tests!
        $delayedSender = new DelayedSender($sender, $pool);

        $sms = $delayedSender->send($number, $body);

        $this->assertInstanceOf('\SmsSender\Result\ResultInterface', $sms);
        $this->assertEquals($body, $sms->getBody());
        $this->assertEquals($number, $sms->getRecipient());
        $this->assertEquals(ResultInterface::STATUS_QUEUED, $sms->getStatus());

        // and flush it all!
        $delayedSender->flush();
    }
}
