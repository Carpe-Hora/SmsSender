<?php

/**
 * This file is part of the SmsSender package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace SmsSender\Provider;

use SmsSender\Exception as Exception;
use SmsSender\HttpAdapter\HttpAdapterInterface;
use SmsSender\Result\ResultInterface;

/**
 * @author Thomas Konrad <tkonrad@gmx.net>
 * @see <https://websms.at/entwickler/sms-api/api-rest/rest-schnittstellenspezifikation>
 */
class WebsmsProvider extends AbstractProvider
{
    /**
     * @var string
     */
    const ENDPOINT_URL = 'https://api.websms.com/rest/smsmessaging/text';

    /**
     * @var string
     */
    protected $accessToken;

    protected $internationalPrefix;

    /**
     * {@inheritDoc}
     */
    public function __construct(HttpAdapterInterface $adapter, $accessToken, $internationalPrefix = '+43')
    {
        parent::__construct($adapter);

        $this->accessToken = $accessToken;
        $this->internationalPrefix = $internationalPrefix;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'websms';
    }

    /**
     * Send a message to the given phone number.
     *
     * @param string $recipient  The phone number.
     * @param string $body       The message to send.
     * @param string $originator The name of the person which sends the message.
     * @return array The data returned by the API.
     */
    public function send($recipient, $body, $originator = '')
    {
        if (null === $this->accessToken) {
            throw new Exception\InvalidCredentialsException('No API credentials provided');
        }

        $params = array(
            'messageContent' => $body,
            'recipientAddressList' => array(
                $this->removeLeadingPlusIfPresent(
                    $this->localNumberToInternational($recipient, $this->internationalPrefix)
                )
            )
        );

        return $this->executeQuery(self::ENDPOINT_URL, $params, array(
            'recipient'  => $recipient,
            'body'       => $body,
            'originator' => $originator,
        ));
    }

    protected function executeQuery($url, array $data = array(), array $extra_result_data = array())
    {
        $headers = array(
            sprintf('Authorization: Bearer %s', $this->accessToken),
            'Content-Type: application/json',
            'Accept: application/json'
        );
        $content = $this->getAdapter()->getContent($url, 'POST', $headers, json_encode($data));

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
        $smsData = array();

        // there was an error
        if (empty($data['transferId']) || empty($data['statusCode'])) {
            return array_merge($this->getDefaults(), $extra_result_data, array(
                    'status' => ResultInterface::STATUS_FAILED,
                )
            );
        }

        // Get the transfer id
        $smsData['id'] = $data['transferId'];

        // get the status
        switch ($data['statusCode']) {
            case 2000:
                $smsData['status'] = ResultInterface::STATUS_SENT;
                break;
            case 2001:
                $smsData['status'] = ResultInterface::STATUS_QUEUED;
                break;
            default:
                $smsData['status'] = ResultInterface::STATUS_FAILED;
                break;
        }

        return array_merge($this->getDefaults(), $extra_result_data, $smsData);
    }

    protected function removeLeadingPlusIfPresent($number)
    {
        if ($number[0] !== '+') {
            // The number has no leading "+" sign
            return $number;
        } else {

            // Remove the leading "+" sign and add the prefix
            return substr($number, 1);
        }
    }
}
