<?php

/**
 * This file is part of the SmsSender package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace SmsSender\Provider;

use SmsSender\HttpAdapter\HttpAdapterInterface;
use SmsSender\Provider\ProviderInterface;
use SmsSender\Result\ResultInterface;

/**
 * @author Kévin Gomez <kevin_gomez@carpe-hora.com>
 */
class NexmoProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * @var string
     */
    const SEND_SMS_URL = 'https://rest.nexmo.com/sms/json';

    /**
     * @var string
     */
    protected $api_key;

    /**
     * @var string
     */
    protected $api_secret;

    /**
     * @var string
     */
    protected $international_prefix;

    /**
     * {@inheritDoc}
     */
    public function __construct(HttpAdapterInterface $adapter, $api_key, $api_secret, $international_prefix = '+33')
    {
        parent::__construct($adapter);

        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
        $this->international_prefix = $international_prefix;
    }

    /**
     * {@inheritDoc}
     */
    public function send($recipient, $body, $originator = '')
    {
        if (null === $this->api_key || null === $this->api_secret) {
            throw new \RuntimeException('No API credentials provided');
        }

        if (empty($recipient)) {
            throw new \RuntimeException('The recipient parameter is required.');
        }

        if (empty($originator)) {
            throw new \RuntimeException('The originator parameter is required for this provider.');
        }

        // clean the originator string to ensure that the sms won't be
        // rejected because of this
        $originator = $this->cleanOriginator($originator);

        $params = $this->getParameters(array(
            'to'    => $this->localNumberToInternational($recipient, $this->international_prefix),
            'text'  => $body,
            'from'  => $originator,
            'type'  => $this->containsUnicode($body) ? 'unicode' : 'text',
        ));

        return $this->executeQuery(self::SEND_SMS_URL, $params, array(
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
        return 'nexmo';
    }

    /**
     * @param  string $query
     * @return array
     */
    protected function executeQuery($url, array $data = array(), array $extra_result_data = array())
    {
        $content = $this->getAdapter()->getContent($url, 'POST', $headers = array(), $data);

        if (null === $content) {
            return array_merge($this->getDefaults(), $extra_result_data);
        }

        return $this->parseResults($content, $extra_result_data);
    }

    /**
     * Builds the parameters list to send to the API.
     *
     * @return array
     * @author Kevin Gomez <kevin_gomez@carpe-hora.com>
     */
    public function getParameters(array $additionnal_parameters = array())
    {
        return array_merge(array(
            'username'  => $this->api_key,
            'password'  => $this->api_secret,
        ), $additionnal_parameters);
    }

    /**
     * Parse the data returned by the API.
     *
     * @param  string $result The raw result string.
     * @return array
     */
    protected function parseResults($result, array $extra_result_data = array())
    {
        $data = json_decode($result, true);
        $sms_data = array();

        if (empty($data['message-count']) || $data['message-count'] < 1) {
            return array_merge($this->getDefaults(), $extra_result_data);
        }

        // for now, only consider the first message
        $message = $data['messages'][0];

        // get the id
        $sms_data['id'] = $message['message-id'];

        // get the status
        $sms_data['status'] = $message['status'] === '0'
            ? ResultInterface::STATUS_SENT
            : ResultInterface::STATUS_FAILED;

        return array_merge($this->getDefaults(), $extra_result_data, $sms_data);
    }

    /**
     * Validate an originator string
     *
     * If the originator ('from' field) is invalid, some networks may reject
     * the network whilst stinging you with the financial cost! While this
     * cannot correct them, it will try its best to correctly format them.
     *
     * @param string $number The phone number to clean.
     *
     * @return string The cleaned phone number.
     */
    protected function cleanOriginator($number)
    {
        // Remove any invalid characters
        $ret = preg_replace('/[^a-zA-Z0-9]/', '', (string) $number);

        if (preg_match('/[a-zA-Z]/', $number)) {
            // Alphanumeric format so make sure it's < 11 chars
            $ret = substr($ret, 0, 11);
        } else {
            // Numerical, remove any prepending '00'
            if (substr($ret, 0, 2) == '00') {
                $ret = substr($ret, 2);
                $ret = substr($ret, 0, 15);
            }
        }

        return (string) $ret;
    }

    /**
     * Checks if the given string contains unicode characters.
     *
     * @param string $string The string to test.
     *
     * @return bool
     * @author Kevin Gomez <kevin_gomez@carpe-hora.com>
     */
    protected function containsUnicode($string)
    {
        return max(array_map('ord', str_split($string))) > 127;
    }

    /**
     * Fix a local phone number to transform it to its equivalent international
     * format.
     *
     * @param string $number The phone number to fix.
     * @param string $prefix The prefix to use.
     *
     * @return string The fixed phone number;
     * @author Kevin Gomez <kevin_gomez@carpe-hora.com>
     */
    protected function localNumberToInternational($number, $prefix)
    {
        // already international format
        if ($number[0] === '+') {
            return $number;
        }

        // remove the leading 0 and add the prefix
        return sprintf('%s%s', $prefix, substr($number, 1));
    }
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
