<?php

/**
 * This file is part of the SmsSender package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace SmsSender\Pool;

use SmsSender\SmsSenderInterface;
use SmsSender\Result\ResultInterface;

/**
 * @author Kévin Gomez <kevin_gomez@carpe-hora.com>
 */
class MemoryPool implements PoolInterface
{
    protected $messages;

    public function __construct()
    {
        $this->messages = new \SplQueue();
    }

    /**
     * {@inheritdoc}
     */
    public function enQueue(ResultInterface $message)
    {
        $this->messages[] = $message;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(SmsSenderInterface $sender)
    {
        $results = array();

        foreach ($this->messages as $message) {
            $results[] = $sender->send($message['recipient'], $message['body'], $message['originator']);
        }

        return $results;
    }
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
