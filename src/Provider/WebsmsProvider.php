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

    /**
     * @var string
     */
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
     * {@inheritDoc}
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

    /**
     * Issues the actual HTTP query.
     *
     * @param $url
     * @param array $data
     * @param array $extra_result_data
     * @return array
     */
    protected function executeQuery($url, array $data = array(), array $extra_result_data = array())
    {
        $headers = array(
            sprintf('Authorization: Bearer %s', $this->accessToken),
            'Content-Type: application/json',
            'Accept: application/json'
        );

        // Issue the request
        $content = $this->getAdapter()->getContent($url, 'POST', $headers, json_encode($data));

        if (null === $content) {
            return array_merge($this->getDefaults(), $extra_result_data);
        }

        return $this->parseResults($content, $extra_result_data);
    }

    /**
     * Parses the data returned by the API.
     *
     * @param  string $result The raw result string.
     * @return array
     */
    protected function parseResults($result, array $extra_result_data = array())
    {
        $data = json_decode($result, true);
        $smsData = array();

        // There was an error
        if (empty($data['transferId']) || empty($data['statusCode'])) {
            return array_merge($this->getDefaults(), $extra_result_data, array(
                    'status' => ResultInterface::STATUS_FAILED,
                )
            );
        }

        // Get the transfer id
        $smsData['id'] = $data['transferId'];

        // Get the status
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

    /**
     * Removes the leading plus sign from the international phone number as websms requires it that way.
     *
     * @param string $number The number to strip the plus sign from
     * @return string
     */
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
