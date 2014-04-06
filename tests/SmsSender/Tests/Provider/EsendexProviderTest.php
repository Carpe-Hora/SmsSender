<?php

namespace SmsSender\Tests\Provider;

use SmsSender\Provider\EsendexProvider;
use SmsSender\Result\ResultInterface;

class EsendexProviderTest extends BaseProviderTest
{
    protected function getProvider($adapter)
    {
        return new EsendexProvider($adapter, 'username', 'pass', 'account');
    }

    /**
     * @expectedException           \RuntimeException
     * @expectedExceptionMessage    No API credentials provided
     */
    public function testSendWithNullApiCredentials()
    {
        $adapter = $this->getMock('\SmsSender\HttpAdapter\HttpAdapterInterface');
        $provider = new EsendexProvider($adapter, null, null, null);
        $provider->send('0642424242', 'foo!');
    }

    /**
     * @expectedException           \RuntimeException
     * @expectedExceptionMessage    No API credentials provided
     */
    public function testGetStatusWithNullApiCredentials()
    {
        $adapter = $this->getMock('\SmsSender\HttpAdapter\HttpAdapterInterface');
        $provider = new EsendexProvider($adapter, null, null, null);
        $provider->getStatus('dummyMessageId');
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
        $number = '0642424242';
        $msg = 'foo';

        $sms = array(
            'id'            => null,
            'status'        => ResultInterface::STATUS_FAILED,
            'recipient'     => $number,
            'body'          => $msg,
            'originator'    => '',
        );

        $api_data = <<<EOF
Result=OK
MessageIDs=3c13bbba-a9c2-460c-961b-4d6772960af0
EOF;

        return array(
            array($number, $msg, '',          null,        $sms),
            array(null,    $msg, '',          null,        array_merge($sms, array('recipient' => null))),
            array('',      $msg, '',          null,        array_merge($sms, array('recipient' => ''))),
            array($number, null, '',          null,        array_merge($sms, array('body' => null))),
            array($number, '', '',            null,        array_merge($sms, array('body' => ''))),
            array($number, $msg, '',          $api_data,   array_merge($sms, array('id' => '3c13bbba-a9c2-460c-961b-4d6772960af0', 'status' => ResultInterface::STATUS_SENT))),
            array($number, $msg, 'Superman',  $api_data,   array_merge($sms, array('id' => '3c13bbba-a9c2-460c-961b-4d6772960af0', 'status' => ResultInterface::STATUS_SENT, 'originator' => 'Superman'))),
        );
    }

/*
    public function testSendForReal()
    {
        if (!isset($_SERVER['ESENDEX_API_USER']) || !isset($_SERVER['ESENDEX_API_PASS']) || !isset($_SERVER['ESENDEX_API_ACCOUNT'])) {
            $this->markTestSkipped('You need to configure the ESENDEX_API_USER, ESENDEX_API_PASS, ESENDEX_API_ACCOUNT values in phpunit.xml');
        }

        $this->provider = new EsendexProvider($this->getMockAdapter(), $_SERVER['ESENDEX_API_USER'], $_SERVER['ESENDEX_API_PASS'], $_SERVER['ESENDEX_API_ACCOUNT']);
        $result = $this->provider->send('0642424242', ''); // @todo: get a fake number

        $this->assertEquals('foo', $result['id']);
        $this->assertTrue($result['sent']);
    }
*/
}
