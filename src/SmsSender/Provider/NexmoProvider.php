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
class NexmoProvider extends AbstractProvider
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
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
