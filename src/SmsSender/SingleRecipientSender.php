<?php

/**
 * This file is part of the SmsSender package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace SmsSender;

use SmsSender\Result\Sms;

/**
 * Allows to configure a single recipient strategy.
 *
 * @author William Durand <william.durand1@gmail.com>
 */
class SingleRecipientSender implements SmsSenderInterface
{
    /**
     * @var \SmsSender\SmsSenderInterface
     */
    private $smsSender;

    /**
     * @var string
     */
    private $recipient;

    /**
     * @param \SmsSender\SmsSenderInterface $smsSender An instance of SmsSenderInterface
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
        $result = $this->getSmsSender()->send($this->recipient, $body, $originator);
        $result['recipient'] = $recipient;

        return $result;
    }

    /**
     * @return \SmsSender\SmsSenderInterface
     */
    public function getSmsSender()
    {
        return $this->smsSender;
    }

    /**
     * Allows to proxy method calls to the real SMS sender.
     */
    public function __call($name, $arguments)
    {
        if (is_callable(array($this->smsSender, $name))) {
            $result = call_user_func_array(array($this->smsSender, $name), $arguments);

            // don't break fluid interfaces
            return $result instanceof SmsSenderInterface ? $this : $result;
        }
    }
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
