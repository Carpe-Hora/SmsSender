<?php

namespace SmsSender\Tests\Result;

use SmsSender\Result\Sms;
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
            'sent'       => true,
            'recipient'  => '0642424242',
            'body'       => 'dummy message',
            'originator' => 'Superman',
        );

        $this->sms->fromArray($array);

        $this->assertEquals('42sms42', $this->sms->getId());
        $this->assertTrue($this->sms->isSent());
        $this->assertEquals('dummy message', $this->sms->getBody());
        $this->assertEquals('0642424242', $this->sms->getRecipient());
        $this->assertEquals('Superman', $this->sms->getOriginator());
    }

    public function testToArray()
    {
        $expected = array(
            'id'         => '42foo42',
            'sent'       => true,
            'recipient'  => '0642424242',
            'body'       => 'dummy message',
            'originator' => 'Superman',
        );

        $this->sms->fromArray($expected);
        $result = $this->sms->toArray();

        $this->assertEquals('42foo42', $result['id']);
        $this->assertTrue($result['sent']);
        $this->assertEquals('dummy message', $result['body']);
        $this->assertEquals('0642424242', $result['recipient']);
        $this->assertEquals('Superman', $result['originator']);
    }

    public function testFromDataWithEmptyArray()
    {
        $this->sms->fromArray(array());

        $this->assertNull($this->sms->getId());
        $this->assertNull($this->sms->isSent());
        $this->assertNull($this->sms->getBody());
        $this->assertNull($this->sms->getRecipient());
        $this->assertNull($this->sms->getOriginator());
    }

    public function testFromDataWithNull()
    {
        $array = array(
            'sent'  => true,
            'body'  => 'foo'
        );

        $this->sms->fromArray($array);

        $this->assertNull($this->sms->getId());
        $this->assertTrue($this->sms->isSent());
        $this->assertNull($this->sms->getRecipient());
        $this->assertEquals('foo', $this->sms->getBody());
        $this->assertNull($this->sms->getOriginator());
    }

    public function testArrayInterface()
    {
        $array = array(
            'id'    => '42foo42',
            'sent'  => true
        );

        $this->sms->fromArray($array);

        // array access
        $this->assertEquals('42foo42', $this->sms['id']);
        $this->assertTrue($this->sms['sent']);

        // array access is case independant
        $this->assertEquals('42foo42', $this->sms['ID']);
        $this->assertTrue($this->sms['SENT']);

        // isset
        $this->assertTrue(isset($this->sms['id']));
        $this->assertTrue(isset($this->sms['sent']));
        $this->assertFalse(isset($this->sms['other']));

        // set
        $this->sms['id'] = 'foo';
        $this->assertEquals('foo', $this->sms['id']);

        // unset
        unset($this->sms['id']);
        $this->assertFalse(isset($this->sms['id']));
    }
}
