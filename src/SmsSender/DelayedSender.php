<?php

/**
 * This file is part of the SmsSender package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace SmsSender;

use SmsSender\Pool\PoolInterface;
use SmsSender\Result\ResultInterface;
use SmsSender\Result\Sms;

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class DelayedSender implements DelayedSenderInterface
{
    /**
     * @var SmsSenderInterface
     */
    protected $smsSender;

    /**
     * @var PoolInterface
     */
    protected $pool;

    /**
     * @param SmsSenderInterface $smsSender An instance of SmsSenderInterface
     *                                      to decorate with a delayed sending
     *                                      strategy.
     * @param Poolinterface $recipient The pool to use.
     */
    public function __construct(SmsSenderInterface $smsSender, PoolInterface $pool)
    {
        $this->smsSender = $smsSender;
        $this->pool = $pool;
    }

    /**
     * {@inheritdoc}
     */
    public function send($recipient, $body, $originator = '')
    {
        $message = new Sms();
        $message->fromArray(array(
            'recipient'     => $recipient,
            'body'          => $body,
            'originator'    => $originator,
            'status'        => ResultInterface::STATUS_QUEUED,
        ));

        $this->pool->enqueue($message);

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        return $this->pool->flush($this->smsSender);
    }

    /**
     * @return SmsSenderInterface
     */
    public function getSmsSender()
    {
        return $this->smsSender;
    }

    /**
     * @return PoolInterface
     */
    public function getPool()
    {
        return $this->pool;
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
