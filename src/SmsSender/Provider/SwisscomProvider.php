<?php

/**
 * This file is part of the SmsSender package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace SmsSender\Provider;

use SmsSender\Exception\InvalidCredentialsException;
use SmsSender\Exception\InvalidArgumentException;
use SmsSender\Result\ResultInterface;

/**
 * @author Lucas Bickel <hairmare@purplehaze.ch>
 * @see <https://developer.swisscom.com/documentation/api/sms-messaging-api>
 */
class SwisscomProvider extends GsmaOneApiProvider
{
    /**
     * {@inheritDoc}
     *
     * @param object $adapter   adapter
     * @param string $client_id            API-key from developer.swisscom.com
     * @param string $international_prefix international prefix
     *
     * @return SwisscomProvider
     */
    public function __construct($adapter, $client_id, $international_prefix = '+41')
    {
        parent::__construct($adapter, $international_prefix);

        $this->client_id = $client_id;
        $this->url = 'https://api.swisscom.com/v1/messaging/sms/outbound/%s/requests';
    }

    /**
     * {@inheritDoc}
     */
    public function send($recipient, $body, $originator = '')
    {
        if (null == $this->client_id) {
            throw new InvalidCredentialsException('No API credentials provided');
        }
        return parent::send($recipient, $body, $originator);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'swisscom';
    }

    /**
     * {@inheritDoc}
     */
    protected function getHeaders()
    {
        $headers = parent::getHeaders();
        $headers[] = 'client_id: '.$this->client_id;
        return $headers;
    }
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
