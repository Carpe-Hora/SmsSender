<?php

namespace SmsSender\Tests;

use SmsSender\Result\Sms;
use SmsSender\SingleRecipientSender;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class SingleRecipientSenderTest extends TestCase
{
    public function testSendToTheSingleRecipientNumber()
    {
        // SMS will be sent to this recipient...
        $singleRecipient = '0601010101';
        // ...even if we will send SMS to this phonenumber
        $realRecipient   = '0702020202';
        $body            = 'Hello World!';

        // Result SMS object, will be returned by the embedded sender.
        $result = new Sms();
        $result->fromArray(array(
            'recipient' => $singleRecipient,    // The embedded sender doesn't know nothing about the strategy
            'body'      => $body
        ));

        $sender = new TestableSingleRecipientSender(
            $this->getSenderMock($singleRecipient, $result),
            $singleRecipient
        );

        $sms = $sender->send($realRecipient, $body);

        $this->assertInstanceOf('\SmsSender\Result\ResultInterface', $sms);
        $this->assertSame($result, $sms, 'Ensures there is a unique result object');
        $this->assertEquals($body, $sms->getBody(), 'The body should be the same');
        $this->assertEquals($realRecipient, $sms->getRecipient(), 'Recipient should be modified to show the real recipient number');
        $this->assertEquals($realRecipient, $sender->lastRecipient);
    }

    private function getSenderMock($singleRecipient, $result)
    {
        $mock = $this->getMock('\SmsSender\SmsSenderInterface');
        $mock
            ->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo($singleRecipient),
                $this->isType(\PHPUnit_Framework_Constraint_IsType::TYPE_STRING),
                $this->isEmpty()
            )
            ->will($this->returnValue($result));

        return $mock;
    }
}

class TestableSingleRecipientSender extends SingleRecipientSender
{
    public $lastRecipient = null;

    public function send($recipient, $body, $originator = '')
    {
        $this->lastRecipient = $recipient;

        return parent::send($recipient, $body, $originator);
    }
}
