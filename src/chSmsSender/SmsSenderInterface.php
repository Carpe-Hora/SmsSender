<?php

/**
 * This file is part of the chSmsSender package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace chSmsSender;

/**
 * @author Kévin Gomez <kevin_gomez@carpe-hora.com>
 */
interface SmsSenderInterface
{
    /**
     * Send a message to the given phone number.
     *
     * @param string $recipient  The phone number.
     * @param string $body       The message to send.
     * @param string $originator The name of the person which sends the message.
     *
     * @return \chSmsSender\Result\Sms    A Sms result object.
     */
    function send($recipient, $body, $originator = '');
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
