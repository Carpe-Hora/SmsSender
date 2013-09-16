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
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
interface PoolInterface
{
    /**
     * Add a SMS in the pool.
     *
     * @param \SmsSender\Result\ResultInterface A Sms result object.
     */
    public function enQueue(ResultInterface $message);

    /**
     * Sends all the messages contained in the pool.
     *
     * @param \SmsSender\SmsSenderInterface $sender The sender to use.
     */
    public function flush(SmsSenderInterface $sender);
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
