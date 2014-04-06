<?php

/**
 * This file is part of the SmsSender package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT License
 */

namespace SmsSender\Provider;

use SmsSender\HttpAdapter\HttpAdapterInterface;
use SmsSender\Result\ResultInterface;
use Exception;

/**
 * @author Kevin Saliou <kevin@saliou.name>
 */
class CardboardfishProvider extends AbstractProvider
{
    /**
     * @var string
     */
    const SEND_SMS_URL = 'http://sms1.cardboardfish.com:9001/HTTPSMS';

    /**
     * @var string
     */
    const SMS_STATUS_URL = 'http://sms1.cardboardfish.com:9001/ClientDR/ClientDR';

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
            throw new \RuntimeException('No API credentials provided');
        }

        $res = $this->getAdapter()->getContent(self::SMS_STATUS_URL, 'POST', $headers = array(), $this->getParameters());

        return $this->parseStatusResults($res);
    }

    /**
     * {@inheritDoc}
     */
    public function send($recipient, $body, $originator = '', $user_ref = null)
    {
        if (null === $this->username || null === $this->password) {
            throw new \RuntimeException('No API credentials provided');
        }

        $params = $this->getParameters(array(
            'DA' => $recipient,
            'SA' => $originator,
            'UR' => $user_ref,
            'M'  => $this->getMessage($body),
        ));
        $extra_result_data = array(
            'recipient'  => $recipient,
            'body'       => $body,
            'originator' => $originator,
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
        return 'cardboardfish';
    }

    /**
     * Builds the parameters list to send to the API.
     *
     * @return array
     */
    protected function getParameters(array $additionnal_parameters = array())
    {
        return array_filter(
            array_merge(array(
                /*
                 * -- system type
                 * Must be set to value: H
                 */
                'S'  => 'H',
                /*
                 * Account username (case sensitive)
                 */
                'UN' => $this->username,
                /*
                 * Account password (case sensitive)
                 */
                'P'  => $this->password,
                /*
                 * -- dest addr
                 * Destination mobile number in
                 * international format without + prefix.
                 * Up to 10 different numbers can be
                 * supplied separated by commas.
                 */
                'DA' => null,
                /*
                 * -- source addr
                 * Originator address (sender id). Up to
                 * 16 numeric or 11 alphanumeric
                 * characters.
                 */
                'SA' => null,
                /*
                 * -- message
                 * The SMS text for plain messages or
                 * UCS2 hex for Unicode. For binary,
                 * hex encoded 8-bit data.
                 */
                'M'  => null,
                 /*
                  * -- user data header
                  * Hex encoded UDH to be used with
                  * binary messages.
                  */
                'UD' => null,
                /*
                 * user reference
                 * -- Unique reference supplied by user to
                 * aid with matching delivery receipts.
                 * Maximum 16 alphanumeric characters
                 * including _ and -.
                 */
                'UR' => null,
                /*
                 * -- validity period
                 * The number of minutes to attempt
                 * delivery before the message expires.
                 * Maximum 10080
                 * Default 1440
                 */
                'VP' => null,
                'V'  => null,
                /*
                 * -- source addr ton
                 * Controls the type of originator where:
                 * 1 = International numeric (eg. +447000000000)
                 * 0 = National numeric (eg. 80050)
                 * 5 = Alphanumeric (eg. CallNow)
                 */
                'ST' => null,
                /*
                 * delay until
                 */
                'DU' => null,
                /*
                 * local time
                 */
                'LC' => null,
                /*
                 * -- data coding scheme
                 * 0 - Flash
                 * 1 - Normal (default)
                 * 2 - Binary
                 * 4 - UCS2
                 * 5 - Flash UCS2
                 * 6 - Flash GSM
                 * 7 - Normal GSM
                 */
                'DC' => 0,
                /*
                 * -- delivery receipt
                 * Controls whether a delivery receipt is
                 * requested for this message where:
                 * 0 = No (default)
                 * 1 = Yes
                 * 2 = Record Only
                 */
                'DR' => null,
            ), $additionnal_parameters
            ), array($this, 'isNotNull')
        );
    }

    protected function isNotNull($var)
    {
        return !is_null($var);
    }

    /**
     * Parses the data returned by the API for a "status" request.
     *
     * @param string $result            The raw result string.
     * @param array  $extra_result_data
     *
     * @return array
     */
    protected function parseStatusResults($result, array $extra_result_data = array())
    {
        $result = trim($result);

        $this->checkForError($result);

        return $this->checkForStatusResult($result);
    }

    /**
     * Parses the data returned by the API for a "send" request.
     *
     * @param string $result            The raw result string.
     * @param array  $extra_result_data
     *
     * @return array
     */
    protected function parseSendResults($result, array $extra_result_data = array())
    {
        $result = trim($result);

        try {
            $this->checkForError($result);
            $this->checkForOk($result);
        } catch (Exception $e) {
            return array_merge($this->getDefaults(), $extra_result_data, array('error' => $e->getMessage()));
        }

        // The message was successfully sent!
        $sms_data = array(
            'status' => ResultInterface::STATUS_SENT,
        );
        $arr = explode(' ', $result);
        if (3 === count($arr)) {
            // eg. OK 375055 UR:LO_5
            list($_, $ref) = explode(':', $arr[2]);
            $sms_data['id'] = $arr[1];
            $sms_data['user_ref'] = $ref;
        } elseif (2 === count($arr)) {
            // eg. OK 375056
            $sms_data['id'] = $arr[1];
        }

        return array_merge($this->getDefaults(), $extra_result_data, $sms_data);
    }

    /**
     * @param  string    $result The raw result string.
     * @throws Exception if error code found
     */
    protected function checkForError($result)
    {
        if ('ERR' !== substr($result, 0, 3)) {
            return;
        }

        switch ($result) {
            case 'ERR -5':
                throw new Exception('Not Enough Credit');
                break;
            case 'ERR -10':
                throw new Exception('Invalid Username or Password');
                break;
            case 'ERR -15':
                throw new Exception('Invalid destination or destination not covered');
                break;
            case 'ERR -20':
                throw new Exception('System error, please retry');
                break;
            case 'ERR -25':
                throw new Exception('Request Error, Do Not Retry');
                break;
            default:
                throw new Exception('Unknown Error');
                break;
        }
    }

    /**
     * @param  string    $result The raw result string.
     * @throws Exception if error code found
     */
    protected function checkForOk($result)
    {
        if ('OK' === substr($result, 0, 2)) {
            return;
        }
        throw new Exception('Unknown Error');
    }

    /**
     * @param  string       $result
     * @return string|false
     */
    protected function checkForStatusResult($result)
    {
        if ('0#' === $result) {
            return 'no message in queue';
        }

        $statuses = array(
            1  => 'DELIVERED', // Message delivered to handset.
            2  => 'BUFFERED', // Message buffered, usually because it failed first time and is now being retried.
            3  => 'FAILED', // The message failed to deliver. The GSM error code may give more information.
            5  => 'EXPIRED', // Message expired, could not be delivered within the validity period.
            6  => 'REJECTED', // Message rejected by SMSC.
            7  => 'ERROR', // SMSC error, message could not be processed this time.
            11 => 'UNKNOWN', // Unknown status, usually generated after 24 hours if no status has been returned from the SMSC.
            12 => 'UNKNOWN', // Unknown status, SMSC returned a non standard status code.
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
                    'type'        => 'INCOMING',
                    'msgid'       => $arr[0],
                    'source'      => $arr[1],
                    'destination' => $arr[2],
                    'status'      => $statuses[(int) $arr[3]],
                    'error_code'  => $arr[4],
                    'datetime'    => $arr[5],
                    'user_ref'    => $arr[6],
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

                $tmp['source']      = $arr[1];
                $tmp['destination'] = $arr[2];
                $tmp['dcs']         = $arr[3]; // Data Coding Scheme
                // ???? EMPTY - $arr[4]
                $tmp['datetime']    = $arr[5];
                $tmp['udh']         = $arr[6];
                $tmp['message']     = $arr[7];

                $ret[] = $tmp;
            }

            return $ret;
        }

        return false;
    }
    /**
     * @param  string $message
     * @param  int    $data_coding_scheme
     * @return string
     */
    protected function getMessage($message, $data_coding_scheme = null)
    {
        if (null === $data_coding_scheme || 1 === $data_coding_scheme) {
            return urlencode(self::GSMEncode($message));
        }

        if (0 === $data_coding_scheme) {
            return urlencode(self::GSMEncode($message));
        }

        return urlencode($message);
    }

    /**
     * @param string $message
     */
    protected static function GSMEncode($message)
    {
        $gsmchar = array (
            "\x0A" => "\x0A",
            "\x0D" => "\x0D",

            "\x24" => "\x02",

            "\x40" => "\x00",

            "\x13" => "\x13",
            "\x10" => "\x10",
            "\x19" => "\x19",
            "\x14" => "\x14",
            "\x1A" => "\x1A",
            "\x16" => "\x16",
            "\x18" => "\x18",
            "\x12" => "\x12",
            "\x17" => "\x17",
            "\x15" => "\x15",

            "\x5B" => "\x1B\x3C",
            "\x5C" => "\x1B\x2F",
            "\x5D" => "\x1B\x3E",
            "\x5E" => "\x1B\x14",
            "\x5F" => "\x11",

            "\x7B" => "\x1B\x28",
            "\x7C" => "\x1B\x40",
            "\x7D" => "\x1B\x29",
            "\x7E" => "\x1B\x3D",

            "\x80" => "\x1B\x65",

            "\xA1" => "\x40",
            "\xA3" => "\x01",
            "\xA4" => "\x1B\x65",
            "\xA5" => "\x03",
            "\xA7" => "\x5F",

            "\xBF" => "\x60",

            "\xC0" => "\x41",
            "\xC1" => "\x41",
            "\xC2" => "\x41",
            "\xC3" => "\x41",
            "\xC4" => "\x5B",
            "\xC5" => "\x0E",
            "\xC6" => "\x1C",
            "\xC7" => "\x09",
            "\xC8" => "\x45",
            "\xC9" => "\x1F",
            "\xCA" => "\x45",
            "\xCB" => "\x45",
            "\xCC" => "\x49",
            "\xCD" => "\x49",
            "\xCE" => "\x49",
            "\xCF" => "\x49",

            "\xD0" => "\x44",
            "\xD1" => "\x5D",
            "\xD2" => "\x4F",
            "\xD3" => "\x4F",
            "\xD4" => "\x4F",
            "\xD5" => "\x4F",
            "\xD6" => "\x5C",
            "\xD8" => "\x0B",
            "\xD9" => "\x55",
            "\xDA" => "\x55",
            "\xDB" => "\x55",
            "\xDC" => "\x5E",
            "\xDD" => "\x59",
            "\xDF" => "\x1E",

            "\xE0" => "\x7F",
            "\xE1" => "\x61",
            "\xE2" => "\x61",
            "\xE3" => "\x61",
            "\xE4" => "\x7B",
            "\xE5" => "\x0F",
            "\xE6" => "\x1D",
            "\xE7" => "\x63",
            "\xE8" => "\x04",
            "\xE9" => "\x05",
            "\xEA" => "\x65",
            "\xEB" => "\x65",
            "\xEC" => "\x07",
            "\xED" => "\x69",
            "\xEE" => "\x69",
            "\xEF" => "\x69",

            "\xF0" => "\x64",
            "\xF1" => "\x7D",
            "\xF2" => "\x08",
            "\xF3" => "\x6F",
            "\xF4" => "\x6F",
            "\xF5" => "\x6F",
            "\xF6" => "\x7C",
            "\xF8" => "\x0C",
            "\xF9" => "\x06",
            "\xFA" => "\x75",
            "\xFB" => "\x75",
            "\xFC" => "\x7E",
            "\xFD" => "\x79"
        );

        // using the NO_EMPTY flag eliminates the need for the shift pop correction
        $chars = preg_split("//", $message, -1, PREG_SPLIT_NO_EMPTY);

        $ret = '';
        foreach ($chars as $char) {
            preg_match("/[A-Za-z0-9!\/#%&\"=\-'<>\?\(\)\*\+\,\.;:]/", $char, $matches);

            if (isset($matches[0])) {
                $ret.= $char;
            } else {
                if (!isset($gsmchar[$char])) {
                    $ret.= "\x20";
                } else {
                    $ret.= $gsmchar[$char];
                }
            }
        }

        return $ret;
    }
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
