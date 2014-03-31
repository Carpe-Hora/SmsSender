<?php

namespace SmsSender\Tests\Provider;

use SmsSender\Provider\CardboardfishProvider;
use SmsSender\Result\ResultInterface;

class CardboardfishProviderTest extends BaseProviderTest
{
    protected function getProvider($adapter)
    {
        return new CardboardfishProvider($adapter, 'username', 'pass', 'account');
    }

    /**
     * @expectedException           \RuntimeException
     * @expectedExceptionMessage    No API credentials provided
     */
    public function testSendWithNullApiCredentials()
    {
        $adapter = $this->getMock('\SmsSender\HttpAdapter\HttpAdapterInterface');
        $provider = new CardboardfishProvider($adapter, null, null, null);
        $provider->send('33642424242', 'foo!');
    }

    /**
     * @expectedException           \RuntimeException
     * @expectedExceptionMessage    No API credentials provided
     */
    public function testGetStatusWithNullApiCredentials()
    {
        $adapter = $this->getMock('\SmsSender\HttpAdapter\HttpAdapterInterface');
        $provider = new CardboardfishProvider($adapter, null, null, null);
        $provider->getStatus();
    }

    /**
     * @dataProvider statusDataprovider
     */
    public function testStatus($api_response, $expected_result)
    {
        $this->provider = new CardboardfishProvider($this->getMockAdapter(null, $api_response), 'username', 'pass');
        $result = $this->provider->getStatus();

        $this->assertEquals($expected_result, $result);
    }

    public function statusDataprovider()
    {
        return array(
            array('0#', 'no message in queue'),
            array('INCOMING=2'.
                    '#1128173:447111111111:447000000000:1:0:1180019698:AF31C0D:'.
                    '#-1:447111111112:447000000003:1::1180019700::48656C6C6F',
                    array(
                        array(
                            'type'        => 'INCOMING',
                            'msgid'       => '1128173',
                            'source'      => '447111111111',
                            'destination' => '447000000000',
                            'status'      => 'DELIVERED',
                            'error_code'  => '0',
                            'datetime'    => '1180019698',
                            'user_ref'    => 'AF31C0D',
                        ),
                        array(
                            'type'        => 'INCOMING',
                            'msgid'       => '-1',
                            'source'      => '447111111112',
                            'destination' => '447000000003',
                            'status'      => 'DELIVERED',
                            'error_code'  => '',
                            'datetime'    => '1180019700',
                            'user_ref'    => '',
                        )
                    )
            ),
            array('1#'.
                    '-1:447111111112:447000000003:4::1180019702::00430061007200640042'.
                    '006f00610072006400460069007300680020002d00200054006800650020004'.
                    'e006500780074002000470065006e00650072006100740069006f006e002000'.
                    '6f00660020004d006f00620069006c00650020004d006500730073006100670'.
                    '069006e0067',
                    array(
                        array(
                            'type'        => 'INCOMING',
                            'source'      => '447111111112',
                            'destination' => '447000000003',
                            'dcs'         => '4',
                            'datetime'    => '1180019702',
                            'udh'         => '',
                            'message'     => '00430061007200640042006f00610072006400460069007300680020002d00200054006800650020004e006500780074002000470065006e00650072006100740069006f006e0020006f00660020004d006f00620069006c00650020004d006500730073006100670069006e0067',
                        )
                    )
            ),
        );
    }

    public function testSendFail()
    {
        $api_response = 'asdfasdfasdf';
        $this->provider = new CardboardfishProvider($this->getMockAdapter(null, $api_response), 'username', 'pass');
        $result = $this->provider->send('123', 'blabla', '456');

        $expected_result = [
            'id'         => null,
            'status'     => 'failed',
            'recipient'  => '123',
            'body'       => 'blabla',
            'originator' => '456',
            'error'      => 'Unknown Error',
        ];
        $this->assertSame($expected_result, $result);
    }

    /**
     * @dataProvider sendDataprovider
     */
    public function testSend($send_to, $send_msg, $send_from, $api_response, $expected_result)
    {
        $this->provider = new CardboardfishProvider($this->getMockAdapter(null, $api_response), 'username', 'pass');
        $result = $this->provider->send($send_to, $send_msg, $send_from);

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

        $api_data = 'OK 1043333914';

        return array(
            array($number, $msg, '',          null,        $sms),
            array(null,    $msg, '',          null,        array_merge($sms, array('recipient' => null))),
            array('',      $msg, '',          null,        array_merge($sms, array('recipient' => ''))),
            array($number, null, '',          null,        array_merge($sms, array('body' => null))),
            array($number, '',   '',          null,        array_merge($sms, array('body' => ''))),
            array($number, $msg, '',          $api_data,   array_merge($sms, array('id' => '1043333914', 'status' => ResultInterface::STATUS_SENT))),
            array($number, $msg, 'Superman',  $api_data,   array_merge($sms, array('id' => '1043333914', 'status' => ResultInterface::STATUS_SENT, 'originator' => 'Superman'))),
        );
    }
}
