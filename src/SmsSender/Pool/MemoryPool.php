<?php

/**
 * This file is part of the SmsSender package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace SmsSender\Pool;

use SmsSender\Exception\Exception;
use SmsSender\SmsSenderInterface;
use SmsSender\Result\ResultInterface;

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
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
        $errors = array();

        foreach ($this->messages as $message) {
            try {
                $results[] = $sender->send($message['recipient'], $message['body'], $message['originator']);
            } catch (Exception $e) {
                $errors[] = $e;
            }
        }

        return array($results, $errors);
    }
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
