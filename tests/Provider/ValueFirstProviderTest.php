<?php

namespace SmsSender\Tests\Provider;

use SmsSender\Provider\ValueFirstProvider;
use SmsSender\Result\ResultInterface;
use SmsSender\Tests\TestCase;

class ValueFirstProviderTest extends TestCase
{
    protected function getProvider($adapter)
    {
        return new ValueFirstProvider($adapter, 'username', 'pass');
    }

    /**
     * @expectedException           \SmsSender\Exception\InvalidCredentialsException
     * @expectedExceptionMessage    No API credentials provided
     */
    public function testSendWithNullApiCredentials()
    {
        $adapter = $this->getMock('\SmsSender\HttpAdapter\HttpAdapterInterface');
        $provider = new ValueFirstProvider($adapter, null, null, null);
        $provider->send('91919999999', 'foo!');
    }

    /**
     * @expectedException           \SmsSender\Exception\InvalidCredentialsException
     * @expectedExceptionMessage    No API credentials provided
     */
    public function testGetStatusWithNullApiCredentials()
    {
        $adapter = $this->getMock('\SmsSender\HttpAdapter\HttpAdapterInterface');
        $provider = new ValueFirstProvider($adapter, null, null, null);
        $provider->getStatus('dummyMessageId');
    }

    /**
     * @expectedExceptionMessage    The specified message does not conform to DTD
     * @expectedException           \SmsSender\Exception\RuntimeException
     */
    public function testGeneralError()
    {
        $api_data = <<<EOF
<?xml version="1.0" encoding="ISO-8859-1"?>
<MESSAGEACK>
    <Err Code="65535" Desc="The Specified message does not conform to DTD"/>
</MESSAGEACK>
EOF;
        $provider = $this->getProvider($this->getMockAdapter(null, $api_data));
        $provider->send('9191000000', 'foo', '9999');
    }

    public function testStatusCreditSuccess()
    {
        $api_data = <<<EOF
<?xml version="1.0" encoding="ISO-8859-1"?>
<SMS-Credit User="foo">
    <Credit Limit="1000000" Used="4007.00"/>
</SMS-Credit>
EOF;
        $provider = $this->getProvider($this->getMockAdapter(null, $api_data));
        $expected = array(
            'user'  => 'foo',
            'limit' => 1000000,
            'used'  => 4007,
        );
        $this->assertEquals($expected, $provider->getCredit());
    }

    /**
     * @expectedExceptionMessage    Username / Password incorrect
     * @expectedExceptionCode       52992
     * @expectedException           \SmsSender\Exception\RuntimeException
     */
    public function testStatusCreditWrongCredentials()
    {
        $api_data = <<<EOF
<?xml version="1.0" encoding="ISO-8859-1"?>
<SMS-Credit User="foo">
    <Err Code="52992" Desc="UserName Password Incorrect"/>
</SMS-Credit>
EOF;
        $provider = $this->getProvider($this->getMockAdapter(null, $api_data));
        $provider->getCredit();
    }

    /**
     * @expectedExceptionMessage    GUID not found
     * @expectedExceptionCode       -1
     * @expectedException           \SmsSender\Exception\RuntimeException
     */
    public function testStatusRequestNoExistingRef()
    {
        $api_data = <<<EOF
<?xml version="1.0" encoding="ISO-8859-1"?>
<STATUSACK>
    <GUID GUID="ke3rg342259821f440014czdy2RAPIDOSPOQ"></GUID>
</STATUSACK>
EOF;
        $provider = $this->getProvider($this->getMockAdapter(null, $api_data));
        $provider->getStatus('ke3rg342259821f440014czdy2RAPIDOSPOQ');
    }

    public function testStatusRequestSuccess()
    {
        $api_data = <<<EOF
<?xml version="1.0" encoding="ISO-8859-1"?>
<STATUSACK>
    <GUID GUID="ke3rg342259821f440014czdy2RAPIDOSPOR">
        <STATUS SEQ="1" ERR="8448" DONEDATE="2014-03-27 16:34:34" REASONCODE="000" />
    </GUID>
</STATUSACK>
EOF;
        $provider = $this->getProvider($this->getMockAdapter(null, $api_data));
        $expected = array(
            'id'            => 'ke3rg342259821f440014czdy2RAPIDOSPOR',
            'status'        => ResultInterface::STATUS_SENT,
            'status_code'   => 8448,
            'status_detail' => 'Message delivered successfully',
        );
        $this->assertEquals($expected, $provider->getStatus('ke3rg342259821f440014czdy2RAPIDOSPOR'));
    }

    /**
     * @expectedExceptionMessage    API response isn't a valid XML string
     * @expectedException           \SmsSender\Exception\RuntimeException
     */
    public function testStatusRequestFail()
    {
        $provider = $this->getProvider($this->getMockAdapter(null, 'not xml'));
        $provider->getStatus('ke3rg342259821f440014czdy2RAPIDOSPOR');
    }

    /**
     * @expectedExceptionMessage    Invalid Receiver ID (will validate Indian mobile numbers only.)
     * @expectedExceptionCode       28682
     * @expectedException           \SmsSender\Exception\RuntimeException
     */
    public function testPostMessageError()
    {
        $api_data = <<<EOF
<?xml version="1.0" encoding="ISO-8859-1"?>
<MESSAGEACK>
    <GUID GUID="ke3ql554883131f440014p89lcRAPIDOSPOR" SUBMITDATE="2014-03-26 21:55:48" ID="1">
        <ERROR SEQ="1" CODE="28682" />
    </GUID>
</MESSAGEACK>
EOF;
        $provider = $this->getProvider($this->getMockAdapter(null, $api_data));
        $provider->send('9191000000', 'foo', '9999');
    }

    /**
     * @dataProvider sendDataprovider
     */
    public function testSend($send_to, $send_msg, $send_from, $api_response, $expected_result)
    {
        $provider = $this->getProvider($this->getMockAdapter(null, $api_response));
        $result = $provider->send($send_to, $send_msg, $send_from);

        $this->assertSame($expected_result['id'], $result['id']);
        $this->assertSame($expected_result['status'], $result['status']);
        $this->assertSame($expected_result['recipient'], $result['recipient']);
        $this->assertSame($expected_result['body'], $result['body']);
        $this->assertSame($expected_result['originator'], $result['originator']);
    }

    public function sendDataprovider()
    {
        $number = '91919999999';
        $msg = 'foo';

        $sms = array(
            'id'            => null,
            'status'        => ResultInterface::STATUS_FAILED,
            'recipient'     => $number,
            'body'          => $msg,
            'originator'    => '',
        );

        $api_data = <<<EOF
<?xml version="1.0" encoding="ISO-8859-1"?>
<MESSAGEACK>
    <GUID GUID="ke3rg342259821f440014czdy2RAPIDOSPOR" SUBMITDATE="2014-03-27 16:34:22" ID="1"></GUID>
</MESSAGEACK>
EOF;

        return array(
            array($number, $msg, '',          null,        $sms),
            array($number, null, '',          null,        array_merge($sms, array('body' => null))),
            array($number, '',   '',          null,        array_merge($sms, array('body' => ''))),
            array($number, $msg, '',          $api_data,   array_merge($sms, array('id' => 'ke3rg342259821f440014czdy2RAPIDOSPOR', 'status' => ResultInterface::STATUS_SENT))),
            array($number, $msg, 'Superman',  $api_data,   array_merge($sms, array('id' => 'ke3rg342259821f440014czdy2RAPIDOSPOR', 'status' => ResultInterface::STATUS_SENT, 'originator' => 'Superman'))),
        );
    }

    /**
     * @dataProvider        incorrectNumberProvider
     * @expectedException   \SmsSender\Exception\InvalidPhoneNumberException
     */
    public function testSendWrongNumberValidation($send_to, $send_msg)
    {
        $provider = $this->getProvider($this->getMockAdapter($this->never()));
        $provider->send($send_to, $send_msg, '999999');
    }

    public function incorrectNumberProvider()
    {
        return array(
            array(null,         'foo'),
            array('',           'foo'),
            array('336666666',  'foo'),
        );
    }
}
