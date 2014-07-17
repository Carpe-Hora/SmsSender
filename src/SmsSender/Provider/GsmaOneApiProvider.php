<?php

/**
 * This file is part of the SmsSender package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace SmsSender\Provider;

use SmsSender\Exception\InvalidArgumentException;
use SmsSender\Result\ResultInterface;

/**
 * @author Lucas Bickel <hairmare@purplehaze.ch>
 * @see <http://www.gsma.com/oneapi/sms-restful-api/>
 */
abstract class GsmaOneApiProvider extends AbstractProvider
{
    /**
     * {@inheritDoc}
     *
     * @param object $adapter              adapter
     * @param string $international_prefix international prefix
     *
     * @return SwisscomProvider
     */
    public function __construct($adapter, $international_prefix = '+41')
    {
        parent::__construct($adapter);

        $this->international_prefix = $international_prefix;
    }

    /**
     * gsma oneapi url
     *
     * Must contain an unique %s placeholder corresponding to the senderAddress
     * (as specified in the specs).
     *
     * @return string
     */
    abstract protected function getEndPointUrl();

    /**
     * {@inheritDoc}
     */
    public function send($recipient, $body, $originator = '')
    {
        if (empty($originator)) {
            throw new InvalidArgumentException('The originator parameter is required for this provider.');
        }

        $internationalOriginator = $this->localNumberToInternational($originator, $this->international_prefix);
        $url = sprintf($this->getEndPointUrl(), urlencode('tel:' . $internationalOriginator));

        $data = array(
            'to'   => $this->localNumberToInternational($recipient, $this->international_prefix),
            'text' => $body,
            'from' => $originator,
        );

        return $this->executeQuery($url, $data, array(
            'recipient'  => $recipient,
            'body'       => $body,
            'originator' => $originator,
        ));
    }

    /**
     * do the query
     */
    private function executeQuery($url, array $data = array(), array $extra_result_data = array())
    {
        $request = array(
            'outboundSMSMessageRequest' => array(
                'address'                => array(sprintf('tel:%s', $data['to'])),
                'senderAddress'          => sprintf('tel:%s', $data['from']),
                'outboundSMSTextMessage' => array(
                    'message' => $data['text']
                ),
            ),
        );

        $content = $this->getAdapter()->getContent($url, 'POST', $this->getHeaders(), json_encode($request));

        if (null == $content) {
            $results = $this->getDefaults();
        }

        if (is_string($content)) {
            $content = json_decode($content, true);
            $results['id'] = $content['outboundSMSMessageRequest']['clientCorrelator'];

            switch ($content['outboundSMSMessageRequest']['deliveryInfoList']['deliveryInfo'][0]['deliveryStatus']) {
                case 'DeliveredToNetwork':
                    $results['status'] = ResultInterface::STATUS_SENT;
                    break;
                case 'DeliveryImpossible':
                    $results['status'] = ResultInterface::STATUS_FAILED;
                    break;
            }
        }

        return array_merge($results, $extra_result_data);
    }

    /**
     * Return headers for request
     *
     * @note Extension point.
     *
     * @return string[]
     */
    protected function getHeaders()
    {
        return array(
            'Content-Type: application/json',
            'Accept: application/json'
        );
    }
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
