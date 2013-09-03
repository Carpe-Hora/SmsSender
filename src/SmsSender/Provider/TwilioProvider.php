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
class TwilioProvider extends AbstractProvider
{
    /**
     * @var string
     */
    const SEND_SMS_URL = 'https://api.twilio.com/2010-04-01/Accounts/%s/SMS/Messages.json';

    /**
     * @var string
     */
    protected $accountSid;

    /**
     * @var string
     */
    protected $authToken;

    /**
     * @var string
     */
    protected $international_prefix;

    /**
     * {@inheritDoc}
     */
    public function __construct(HttpAdapterInterface $adapter, $accountSid, $authToken, $international_prefix = '+33')
    {
        parent::__construct($adapter);

        $this->accountSid = $accountSid;
        $this->authToken = $authToken;
        $this->international_prefix = $international_prefix;
    }

    /**
     * {@inheritDoc}
     */
    public function send($recipient, $body, $originator = '')
    {
        if (null === $this->accountSid || null === $this->authToken) {
            throw new \RuntimeException('No API credentials provided');
        }

        if (empty($originator)) {
            throw new \RuntimeException('The originator parameter is required for this provider.');
        }

        // clean the originator string to ensure that the sms won't be
        // rejected because of this
        $originator = $this->cleanOriginator($originator);

        $params = array(
            'To'    => $this->localNumberToInternational($recipient, $this->international_prefix),
            'Body'  => $body,
            'From'  => $originator,
        );

        return $this->executeQuery(sprintf(self::SEND_SMS_URL, $this->accountSid), $params, array(
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
        return 'twilio';
    }

    /**
     * @param  string $query
     * @return array
     */
    protected function executeQuery($url, array $data = array(), array $extra_result_data = array())
    {
        $headers = array(
            sprintf('Authorization: Basic %s', base64_encode(sprintf('%s:%s', $this->accountSid, $this->authToken))),
        );
        $content = $this->getAdapter()->getContent($url, 'POST', $headers, $data);

        if (null === $content) {
            return array_merge($this->getDefaults(), $extra_result_data);
        }

        return $this->parseResults($content, $extra_result_data);
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

        // there was an error
        if (empty($data['sid']) || !empty($data['message'])) {
            return array_merge($this->getDefaults(), $extra_result_data, array(
                'status' => ResultInterface::STATUS_FAILED,
            ));
        }

        // get the id
        $sms_data['id'] = $data['sid'];

        // get the status
        switch ($data['status']) {
            case 'failed':
                $sms_data['status'] = ResultInterface::STATUS_FAILED;
                break;
            case 'received':
                $sms_data['status'] = ResultInterface::STATUS_DELIVERED;
                break;
            default:
                $sms_data['status'] = ResultInterface::STATUS_SENT;
                break;
        }

        return array_merge($this->getDefaults(), $extra_result_data, $sms_data);
    }
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
