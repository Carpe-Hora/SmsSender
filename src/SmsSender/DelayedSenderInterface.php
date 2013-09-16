<?php

/**
 * This file is part of the SmsSender package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace SmsSender;

/**
 * @author Kévin Gomez <kevin_gomez@carpe-hora.com>
 */
interface DelayedSenderInterface extends SmsSenderInterface
{
    /**
     * Sends all the messages contained in the pool.
     *
     * @return array<\SmsSender\Result\ResultInterface> A list of results.
     */
    public function flush();
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
