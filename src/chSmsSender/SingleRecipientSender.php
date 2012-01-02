<?php

/**
 * This file is part of the chSmsSender package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace chSmsSender;

use chSmsSender\Provider\ProviderInterface;
use chSmsSender\Result\Sms;

/**
 * Allows to configure a single recipient strategy.
 *
 * @author William Durand <william.durand1@gmail.com>
 */
class SingleRecipientSender implements SmsSenderInterface
{
    /**
     * @var \chSmsSender\SmsSenderInterface
     */
    private $smsSender;

    /**
     * @var string
     */
    private $recipient;

    /**
     * @param \chSmsSender\SmsSenderInterface $smsSender An instance of SmsSenderInterface
     *                                                      to decorate with a single recipient strategy.
     * @param string $recipient Recipient phonenumber.
     */
    public function __construct(SmsSenderInterface $smsSender, $recipient)
    {
        $this->smsSender = $smsSender;
        $this->recipient = $recipient;
    }

    /**
     * {@inheritdoc}
     */
    public function send($recipient, $body, $originator = '')
    {
        $result = $this->smsSender->send($this->recipient, $body, $originator);
        $result['recipient'] = $recipient;

        return $result;
    }
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
