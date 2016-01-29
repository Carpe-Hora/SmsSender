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

/**
 * @author Kevin Saliou <kevin@saliou.name>
 */
class ValueFirstProvider extends AbstractProvider
{
    /**
     * @var string
     */
    const SEND_SMS_URL = 'http://api.myvaluefirst.com/psms/servlet/psms.Eservice2';

    /**
     * @var string
     */
    const SMS_STATUS_URL = 'http://api.myvaluefirst.com/psms/servlet/psms.Eservice2';

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
     * @param  string $messageId
     * @return array
     */
    public function getStatus($messageId)
    {
        $this->checkCredentials();

        $xml = $this->buildGetStatusPayload($messageId);
        $res = $this->getAdapter()->getContent(
            self::SMS_STATUS_URL,
            'POST',
            $headers = array(),
            array('action' => 'status', 'data' => $xml)
        );

        return $this->parseStatusResponse($res, $messageId);
    }

    /**
     * @return array
     */
    public function getCredit()
    {
        $this->checkCredentials();

        $res = $this->getAdapter()->getContent(
            self::SMS_STATUS_URL,
            'POST',
            $headers = array(),
            array('action' => 'credits', 'data' => $this->buildGetCreditPayload())
        );

        return $this->parseCreditResponse($res);
    }

    /**
     * {@inheritDoc}
     */
    public function send($recipient, $body, $originator = '', $user_ref = null)
    {
        $this->checkCredentials();
        $this->validateRecipient($recipient);

        $params = array(
            'USERNAME' => $this->username,
            'PASSWORD' => $this->password,
            'TEXT'     => $body,
            'FROM'     => $originator,
            'TO'       => $recipient,
        );
        if (null !== $user_ref) {
            $params['TAG'] = $user_ref;
        }

        $xml = $this->buildSendSmsPayload($this->getParameters($params));

        return $this->executeQuery(self::SEND_SMS_URL, $xml, array(
            'recipient'  => $recipient,
            'body'       => $body,
            'originator' => $originator,
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'valuefirst';
    }

    /**
     * @param  string $url
     * @param  string $xml
     * @param  array  $extra_result_data
     * @return array
     */
    protected function executeQuery($url, $xml, array $extra_result_data = array())
    {
        $res = $this->getAdapter()->getContent($url, 'POST', $headers = array(), array('action' => 'send', 'data' => $xml));

        if (null === $res) {
            return array_merge($this->getDefaults(), $extra_result_data);
        }

        return $this->parseSendResponse($res, $extra_result_data);
    }

    /**
     * @param  string    $recipient
     * @throws Exception is $recipient is not a valid Indian number
     */
    protected function validateRecipient($recipient)
    {
        $validPrefixes = array(
            '9191',
            '9192',
            '9193',
            '9194',
            '9196',
            '9197',
            '9198',
            '9199',
        );

        if (!in_array(substr($recipient, 0, 4), $validPrefixes)) {
            throw new Exception\InvalidPhoneNumberException($recipient . ' is not a valid number');
        }
    }

    /**
     * Encodes the message according to doc
     * @param  string $msg
     * @return string
     */
    protected function encodeMessage($msg)
    {
        $encodings = array(
            9  => '&#009;',
            10 => '&#010;',
            13 => '&#013;',
            32 => '&#032;',
            34 => '&quot;',
            39 => '&apos;',
        );

        $ret = array();
        for ($j = 0; $j < strlen($msg); $j++) {
            $str = $msg[$j];
            $asci = ord($str);
            if (isset($encodings[ $asci ])) {
                $ret[] = $encodings[ $asci ];
            } elseif ($asci >= 128 || $asci < 32 || in_array($str, array('*', '#', '%', '<', '>', '+' ))) {
                $ret[] = strtoupper('%' . sprintf("%02s", dechex($asci)));
            } else {
                $ret[] = $str;
            }
        }

        return implode('', $ret);
    }

    /**
     * Constructs valid XML for sending SMS-CR credit request service
     * @return string
     */
    protected function buildGetCreditPayload()
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="ISO-8859-1"?>'.
            '<!DOCTYPE REQUESTCREDIT SYSTEM "http://127.0.0.1:80/psms/dtd/requestcredit.dtd">'.
            '<REQUESTCREDIT></REQUESTCREDIT>'
        );

        $xml->addAttribute('USERNAME', $this->username);
        $xml->addAttribute('PASSWORD', $this->password);

        return $xml->asXml();
    }

    /**
     * Constructs valid XML for sending SMS-SR request service
     * @param  string $messageId
     * @return string
     */
    protected function buildGetStatusPayload($messageId)
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="ISO-8859-1"?>'.
            '<!DOCTYPE STATUSREQUEST SYSTEM "http://127.0.0.1:80/psms/dtd/requeststatusv12.dtd">'.
            '<STATUSREQUEST VER="1.2"></STATUSREQUEST>'
        );

        $user = $xml->addChild('USER');
        $user->addAttribute('USERNAME', $this->username);
        $user->addAttribute('PASSWORD', $this->password);

        $guid = $xml->addChild('GUID');
        $guid->addAttribute('GUID', $messageId);

        return $xml->asXml();
    }

    /**
     * Constructs valid XML for sending SMS-MT message
     * @param  array  $data
     * @return string
     */
    protected function buildSendSmsPayload(array $data = array())
    {
        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="ISO-8859-1"?>'.
            '<!DOCTYPE MESSAGE SYSTEM "http://127.0.0.1:80/psms/dtd/messagev12.dtd">'.
            '<MESSAGE VER="1.2"></MESSAGE>'
        );

        $user = $xml->addChild('USER');
        foreach (array('USERNAME', 'PASSWORD') as $key) {
            if (!isset($data[$key])) {
                continue;
            }

            $user->addAttribute($key, $data[$key]);
        }

        $sms = $xml->addChild('SMS');
        foreach (array('UDH', 'CODING', 'PROPERTY', 'ID', 'TEXT', 'DLR', 'VALIDITY', 'SEND_ON') as $key) {
            if (!isset($data[$key])) {
                continue;
            }

            if ('TEXT' === $key) {
                $sms->addAttribute($key, $this->encodeMessage($data[$key]));
            } else {
                $sms->addAttribute($key, $data[$key]);
            }
        }

        $address = $sms->addChild('ADDRESS');
        foreach (array('FROM', 'TO', 'SEQ', 'TAG') as $k) {
            if (isset($data[ $k ])) {
                $address->addAttribute($k, $data[ $k ]);
            }
        }

        return $xml->asXml();
    }

    /**
     * Builds the parameters list to send to the API.
     *
     * @return array
     */
    public function getParameters(array $additionnal_parameters = array())
    {
        $defaults = array(
            // ------------- USER TAG -------------
            /*
             * User name of the sender of the message.
             */
            'USERNAME' => null,
            /*
             * User password
             */
            'PASSWORD' => null,

            // ------------- SMS TAG -------------
            /*
             * UDH is used for sending binary messages.
             * For text message the value should be 0.
             */
            'UDH' => 0,
            /*
             * Extended type of messages.
             * For text message the value should be 1.
             */
            'CODING' => 1,
            /*
             * Unique property of message.
             * Default value is 0.
             * For sending Flash SMS the value should be 1.
             */
            'PROPERTY' => 0,
            /*
             * Unique ID of message. The client sends this value. In future communication,
             * server sends this value back to the client. This value is used in future to check
             * status of the message.
             */
            'ID' => 1,
            /*
             * This field describe the message text to be sent to receiver.
             * SMS can contain up to 160 characters in Message Text.
             * API allows user to send Message text of more than 160 characters.
             * Credits will be deducted in the multiple of 160 characters according to the length of SMS.
             */
            'TEXT' => null,
            /*
             * Delivery Report
             * Accepted Values are 0 and 1.
             * When set to 0, the service shall not ask operator for delivery report.
             * Default is 1.
             * This parameter is optional
             */
            'DLR' => null,
            /*
             * Set the validity of a message to current SMSC time plus minutes specified in Validity field.
             * SMSC will not try to send the message after the validity has expired.
             */
            'VALIDITY' => null,
            /*
             * To schedule message to go at a later time, user can specify "SEND_ON" date as attribute of SMS tag.
             * Only absolute date is supported.
             * The value should be given in "YYYY-MM-DD HH:MM:SS TIMEZONE" format (eg. 2007-10-15 20:10:10 +0530)
             * Timezone is difference wrt to GMT.
             */
            'SEND_ON' => null,

            // ------------- ADDRESS TAG -------------
            // <ADDRESS FROM="9812345678" TO="919812345678" SEQ="1" />
            /*
             * The Sender of the message.
             * This field should conform to Sender Phone Number guidelines
             */
            'FROM' => null,
            /*
             * Person receiving the SMS, should conform to Receiver Phone Number guidelines
             */
            'TO' => null,
            /*
             * Unique Sequence ID.
             * Must be an integer and must be unique to each SMS.
             * While checking message status you must send this value.
             */
            'SEQ' => 1,
            /*
             * A text that identify message.
             * This is an optional parameter
             */
            'TAG' => uniqid(), // null,
        );

        return array_filter(
            array_merge($defaults, $additionnal_parameters),
            array($this, 'isNotNull')
        );
    }

    protected function isNotNull($var)
    {
        return !is_null($var);
    }

    /**
     * Parses the data returned by the API.
     *
     * @param  string    $result            The raw result string.
     * @param  array     $extra_result_data
     * @return array
     * @throws Exception if error code found
     */
    protected function parseSendResponse($result, array $extra_result_data = array())
    {
        libxml_use_internal_errors(true);
        if (false === ($result = simplexml_load_string(trim($result)))) {
            throw new Exception\RuntimeException('API response isn\'t a valid XML string');
        }

        if (null !== ($error = $this->checkForError($result))) {
            throw $error;
        }

        // The message was successfully sent!
        return array_merge($this->getDefaults(), $extra_result_data, array(
            'status'    => ResultInterface::STATUS_SENT,
            'id'        => (string) $result->GUID['GUID']
        ));
    }

    /**
     * @param SimpleXMLElement $result The raw result string.
     *
     * @return Exception\Exception
     */
    protected function checkForError(\SimpleXMLElement $result)
    {
        /* -- sample general error --
        <?xml version="1.0" encoding="ISO-8859-1"?>
        <MESSAGEACK>
            <Err Code="65535" Desc="The Specified message does not conform to DTD"/>
        </MESSAGEACK>
        */
        if (0 !== $result->Err->count()) {
            $code = (int) $result->Err['Code'];
            throw new Exception\RuntimeException($this->getApiMessage($code), $code);
        }

        /* -- sample message post error --
        <?xml version="1.0" encoding="ISO-8859-1"?>
        <MESSAGEACK>
            <GUID GUID="ke3ql370590732f440014mucv1RAPIDOSPOR" SUBMITDATE="2014-03-26 21:37:05" ID="1">
                <ERROR SEQ="1" CODE="28682" />
            </GUID>
        </MESSAGEACK>
        */
        if (0 !== $result->GUID->ERROR->count()) {
            $code = (int) $result->GUID->ERROR['CODE'];
            throw new Exception\RuntimeException($this->getApiMessage($code), $code);
        }
    }

    /**
     * @param int $code
     *
     * @return string The message.
     */
    protected function getApiMessage($code)
    {
        $errors = array(
            -1    => 'GUID not found',

            // General
            0     => 'SMS submitted success NO',
            52992 => 'Username / Password incorrect',
            57089 => 'Contract expired',
            57090 => 'User Credit expired',
            57091 => 'User disabled',
            65280 => 'Service is temporarily unavailable',
            65535 => 'The specified message does not conform to DTD',

            // Message Post
            28673 => 'Destination number not numeric',
            28674 => 'Destination number empty',
            28675 => 'Sender address empty',
            28676 => 'SMS over 160 character',
            28677 => 'UDH is invalid',
            28678 => 'Coding is invalid',
            28679 => 'SMS text is empty',
            28680 => 'Invalid sender ID',
            28681 => 'Invalid message. Submit failed',
            28682 => 'Invalid Receiver ID (will validate Indian mobile numbers only.)',
            28683 => 'Invalid Date time for message Schedule (If the date specified in message post for schedule delivery is less than current date or more than expiry date or more than 1 year)',

            // Status Request
            8448 => 'Message delivered successfully',
            8449 => 'Message failed',
            8450 => 'Message ID is invalid',

            // Scheduler Related
            13568 => 'Command Completed Successfully',
            13569 => 'Cannot update/delete schedule since it has already been processed',
            13570 => 'Cannot update schedule since the new date-time parameter is incorrect.',
            13571 => 'Invalid SMS ID/GUID',
            13572 => 'Invalid Status type for schedule search query. The status strings can be "PROCESSED", "PENDING" and "ERROR".',
            13573 => 'Invalid date time parameter for schedule search query',
            13574 => 'Invalid GUID for GUID search query',
            13575 => 'Invalid command action',
        );

        return !empty($errors[$code]) ? $errors[$code] : 'Unknown error code';
    }

    /**
     * @param  string $xml
     * @param  string $messageId
     * @return array
     */
    protected function parseStatusResponse($xml, $messageId)
    {
        /*
        GUID
            A globally unique Message ID that is generated for each <SMS> tag.
            This GUID is generated when ValueFirst Pace receives a new session.
        SEQ
            The address (Mobile No.) SEQ ID (Client side value) whose status was queried
        DONEDATE
            The time when the new status was received.
            The new status could be either success or failure,
            the field is in Standard ANSI format, i.e. YYYY- MM-DD HH:MM:SS
        ERR
            Error / Message Status Code,
            if no standard error occurred, the ERR shall be either one of the following value.
                8448: Message was successfully delivered on DONEDATE
                8449: Message reportedly failed on DONEDATE
        REASONCODE
            In case of failure (8449) service returns reason code for message failure.
            This is a value provided by SMSC and differs for each SMSC route.
            Customers are required to contact ValueFirst to discuss various reason code and
            corresponding meaning for different country.
            Reason-code is an optional variable.
            Note: If a message delivery is tried on a user handset whose number exists in DNC,
            the messages will fail immediately with error-code 999.
            ValueFirst will not charge any credits for such events.
        */
        libxml_use_internal_errors(true);

        if (false === ($result = simplexml_load_string($xml))) {
            throw new Exception\RuntimeException('API response isn\'t a valid XML string');
        }

        if (0 === $result->GUID->STATUS->count()) {
            throw new Exception\RuntimeException($this->getApiMessage(-1), -1);
        }

        $code = (int) $result->GUID->STATUS['ERR'];

        return array(
            'id'            => $messageId,
            'status'        => 8448 === $code || 13568 === $code ? ResultInterface::STATUS_SENT : ResultInterface::STATUS_FAILED,
            'status_code'   => $code,
            'status_detail' => $this->getApiMessage($code),
        );
    }

    /**
     * @param  string $xml
     * @return array
     */
    protected function parseCreditResponse($xml)
    {
        /*
        // response sample
        <?xml version="1.0" encoding="ISO-8859-1"?>
        <SMS-Credit User="rapidosports">
            <Credit Limit="1000000" Used="4007.00"/>
        </SMS-Credit>
         */

        /*
        // error
        <?xml version="1.0" encoding="ISO-8859-1"?>
        <SMS-Credit User="rapidoports">
            <Err Code="52992" Desc="UserName Password Incorrect"/>
        </SMS-Credit>
         */
        libxml_use_internal_errors(true);

        if (false === ($result = simplexml_load_string($xml))) {
            throw new Exception\RuntimeException('API response isn\'t a valid XML string');
        }

        if (0 !== $result->Err->count()) {
            $code = (int) $result->Err['Code'];

            throw new Exception\RuntimeException($this->getApiMessage($code), $code);
        }

        return array(
            'user'  => (string) $result['User'],
            'limit' => (int) $result->Credit['Limit'],
            'used'  => (int) $result->Credit['Used'],
        );
    }

    /**
     * Checks that the needed credentials were given and raise an error if
     * they weren't.
     */
    protected function checkCredentials()
    {
        if (null === $this->username || null === $this->password) {
            throw new Exception\InvalidCredentialsException('No API credentials provided');
        }
    }
}
