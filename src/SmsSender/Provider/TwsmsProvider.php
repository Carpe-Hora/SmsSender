<?php
/**
 * This file is part of the SmsSender package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace SmsSender\Provider;

use SmsSender\Exception as Exception;
use SmsSender\HttpAdapter\HttpAdapterInterface;
use SmsSender\Result\ResultInterface;

class TwsmsProvider extends AbstractProvider
{
    /**
     * @var string
     */
    const SEND_SMS_URL = 'https://api.twsms.com/smsSend.php';

    /**
     * @var string
     */
    const SMS_STATUS_URL = 'https://api.twsms.com/smsQuery.php';

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * {@inheritDoc}
     */
    public function __construct(HttpAdapterInterface $adapter, $username, $password)
    {
        parent::__construct($adapter);

        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Retrieves the queued delivery receipts
     *
     * @return array
     */
    public function getStatus()
    {
        if (null === $this->username || null === $this->password) {
            throw new Exception\InvalidCredentialsException('No API credentials provided');
        }

        $res = $this->getAdapter()->getContent(
            self::SMS_STATUS_URL,
            'POST',
            $headers = array(),
            $this->getParameters()
        );

        return $this->parseStatusResults($res);
    }

    /**
     * {@inheritDoc}
     */
    public function send($recipient, $body, $sendtime = null)
    {
        if (null === $this->username || null === $this->password) {
            throw new Exception\InvalidCredentialsException('No API credentials provided');
        }

        $params = $this->getParameters(
            array(
                'mobile' => $recipient,
                'sendtime' => $sendtime,
                'message' => $this->getMessage($body),
            )
        );

        $extra_result_data = array(
            'recipient' => $recipient,
            'body' => $body,

        );

        $res = $this->getAdapter()->getContent(self::SEND_SMS_URL, 'POST', $headers = array(), $params);

        if (null === $res) {
            return array_merge($this->getDefaults(), $extra_result_data);
        }

        return $this->parseSendResults($res, $extra_result_data);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'twsms';
    }

    /**
     * Builds the parameters list to send to the API.
     *
     * @return array
     */
    protected function getParameters(array $additionnal_parameters = array())
    {
        return array_filter(
            array_merge(
                array(
                    /*
                     * Account username (case sensitive)
                     */
                    'username' => $this->username,
                    /*
                     * Account password (case sensitive)
                     */
                    'password' => $this->password,
                    /*
                     * -- sendtime
                     * 格式：YYYYMMDDHHII （請使用 24 小時制）
                     * 預約時間，例如 201504121830
                     */
                    'sendtime' => null,
                    /*
                     * -- expirytime
                     *簡訊有效期限，單位為秒，範圍：300~86400 秒
                     *例如: 86400 為 24 小時
                     */
                    'expirytime' => null,
                    /*
                     * -- message
                     * The SMS text for plain messages or
                     * UCS2 hex for Unicode. For binary,
                     * hex encoded 8-bit data.
                     */
                    'message' => null,

                ),
                $additionnal_parameters
            ),
            array($this, 'isNotNull')
        );
    }

    protected function isNotNull($var)
    {
        return !is_null($var);
    }

    /**
     * Parses the data returned by the API for a "status" request.
     *
     * @param string $result The raw result string.
     * @param array $extra_result_data
     *
     * @return array
     */
    protected function parseStatusResults($result, array $extra_result_data = array())
    {
        $result = trim($result);

        $this->checkForUnrecoverableError($result);

        return $this->checkForStatusResult($result);
    }

    /**
     * Parses the data returned by the API for a "send" request.
     *
     * @param string $result The raw result string.
     * @param array $extra_result_data
     *
     * @return array
     */
    protected function parseSendResults($result, array $extra_result_data = array())
    {

        $xml = simplexml_load_string($result);

        if ($xml->code <> '00000') {
            return array_merge($this->getDefaults(), $extra_result_data);
        }

        // The message was successfully sent!
        $sms_data['id'] = $xml->msgid;
        $sms_data['code'] = $xml->code;
        $sms_data['status'] = ResultInterface::STATUS_SENT ;

        return array_merge($this->getDefaults(), $extra_result_data, $sms_data);
    }

    /**
     * @param  string $result
     * @return string|false
     */
    protected function checkForStatusResult($result)
    {
        if ('0#' === $result) {
            return 'no message in queue';
        }

        $statuses = array(
            1 => 'DELIVERED',
            // Message delivered to handset.
            2 => 'BUFFERED',
            // Message buffered, usually because it failed first time and is now being retried.
            3 => 'FAILED',
            // The message failed to deliver. The GSM error code may give more information.
            5 => 'EXPIRED',
            // Message expired, could not be delivered within the validity period.
            6 => 'REJECTED',
            // Message rejected by SMSC.
            7 => 'ERROR',
            // SMSC error, message could not be processed this time.
            11 => 'UNKNOWN',
            // Unknown status, usually generated after 24 hours if no status has been returned from the SMSC.
            12 => 'UNKNOWN',
            // Unknown status, SMSC returned a non standard status code.
        );

        $ret = array();
        if ('INCOMING' === substr($result, 0, 8)) {
            // eg. INCOMING=2#1128173:447111111111:447000000000:1:0:1180019698:AF31C0D:#-1:447111111112:447000000003:1::1180019700::48656C6C6F
            foreach (explode('#', $result) as $k => $sms) {
                if (0 === $k) {
                    continue;
                }

                $arr = explode(':', $sms);
                $ret[] = array(
                    'type' => 'INCOMING',
                    'msgid' => $arr[0],
                    'source' => $arr[1],
                    'destination' => $arr[2],
                    'status' => $statuses[(int)$arr[3]],
                    'error_code' => $arr[4],
                    'datetime' => $arr[5],
                    'user_ref' => $arr[6],
                );
            }

            return $ret;
        }

        if (false !== strpos($result, '#')) {
            /* eg.
            1#
            -1:447111111112:447000000003:4::1180019702::00430061007200640042
            006f00610072006400460069007300680020002d00200054006800650020004
            e006500780074002000470065006e00650072006100740069006f006e002000
            6f00660020004d006f00620069006c00650020004d006500730073006100670
            069006e0067
            */
            foreach (explode('#', $result) as $k => $sms) {
                if (0 === $k) {
                    continue;
                }

                $arr = explode(':', $sms);

                if ('-1' === $arr[0]) {
                    $tmp['type'] = 'INCOMING';
                }

                $tmp['source'] = $arr[1];
                $tmp['destination'] = $arr[2];
                $tmp['dcs'] = $arr[3]; // Data Coding Scheme
                // ???? EMPTY - $arr[4]
                $tmp['datetime'] = $arr[5];
                $tmp['udh'] = $arr[6];
                $tmp['message'] = $arr[7];

                $ret[] = $tmp;
            }

            return $ret;
        }

        return false;
    }

    /**
     * @param  string $message
     * @param  int $data_coding_scheme
     * @return string
     */
    protected function getMessage($message, $data_coding_scheme = null)
    {
        return urlencode($message);
    }
}
