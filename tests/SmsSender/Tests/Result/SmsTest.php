<?php

namespace SmsSender\Tests\Result;

use SmsSender\Result\Sms;
use SmsSender\Result\ResultInterface;
use SmsSender\Tests\TestCase;

/**
 * @author Kévin Gomez <kevin_gomez@carpe-hora.com>
 */
class SmsTest extends TestCase
{
    protected $sms;

    protected function setUp()
    {
        $this->sms = new Sms();
    }

    public function testFromArray()
    {
        $array = array(
            'id'         => '42sms42',
            'status'     => ResultInterface::STATUS_SENT,
            'recipient'  => '0642424242',
            'body'       => 'dummy message',
            'originator' => 'Superman',
        );

        $this->sms->fromArray($array);

        $this->assertEquals('42sms42', $this->sms->getId());
        $this->assertTrue($this->sms->isSent());
        $this->assertEquals(ResultInterface::STATUS_SENT, $this->sms->getStatus());
        $this->assertEquals('dummy message', $this->sms->getBody());
        $this->assertEquals('0642424242', $this->sms->getRecipient());
        $this->assertEquals('Superman', $this->sms->getOriginator());
    }

    public function testToArray()
    {
        $expected = array(
            'id'         => '42foo42',
            'status'     => ResultInterface::STATUS_SENT,
            'recipient'  => '0642424242',
            'body'       => 'dummy message',
            'originator' => 'Superman',
        );

        $this->sms->fromArray($expected);
        $result = $this->sms->toArray();

        $this->assertEquals('42foo42', $result['id']);
        $this->assertTrue($result['sent']);
        $this->assertEquals(ResultInterface::STATUS_SENT, $result['status']);
        $this->assertEquals('dummy message', $result['body']);
        $this->assertEquals('0642424242', $result['recipient']);
        $this->assertEquals('Superman', $result['originator']);
    }

    public function testFromDataWithEmptyArray()
    {
        $this->sms->fromArray(array());

        $this->assertNull($this->sms->getId());
        $this->assertFalse($this->sms->isSent());
        $this->assertNull($this->sms->getStatus());
        $this->assertNull($this->sms->getBody());
        $this->assertNull($this->sms->getRecipient());
        $this->assertNull($this->sms->getOriginator());
    }

    public function testFromDataWithNull()
    {
        $array = array(
            'status'  => ResultInterface::STATUS_SENT,
            'body'    => 'foo'
        );

        $this->sms->fromArray($array);

        $this->assertNull($this->sms->getId());
        $this->assertTrue($this->sms->isSent());
        $this->assertEquals(ResultInterface::STATUS_SENT, $this->sms->getStatus());
        $this->assertNull($this->sms->getRecipient());
        $this->assertEquals('foo', $this->sms->getBody());
        $this->assertNull($this->sms->getOriginator());
    }

    public function testArrayInterface()
    {
        $array = array(
            'id'      => '42foo42',
            'status'  => ResultInterface::STATUS_FAILED
        );

        $this->sms->fromArray($array);

        // array access
        $this->assertEquals('42foo42', $this->sms['id']);
        $this->assertEquals(ResultInterface::STATUS_FAILED, $this->sms['status']);

        // array access is case independant
        $this->assertEquals('42foo42', $this->sms['ID']);
        $this->assertEquals(ResultInterface::STATUS_FAILED, $this->sms['STATUS']);

        // isset
        $this->assertTrue(isset($this->sms['id']));
        $this->assertTrue(isset($this->sms['status']));
        $this->assertFalse(isset($this->sms['other']));

        // set
        $this->sms['id'] = 'foo';
        $this->assertEquals('foo', $this->sms['id']);

        // unset
        unset($this->sms['id']);
        $this->assertFalse(isset($this->sms['id']));
    }
}
