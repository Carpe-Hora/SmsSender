<?php

/**
 * This file is part of the SmsSender package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace SmsSender\Provider;

/**
 * @author Kévin Gomez <kevin_gomez@carpe-hora.com>
 */
interface ProviderInterface
{
    /**
     * Send a message to the given phone number.
     *
     * @param string $recipient  The phone number.
     * @param string $body       The message to send.
     * @param string $originator The name of the person which sends the message.
     *
     * @return array             The data returned by the API.
     */
    function send($recipient, $body, $originator = '');

    /**
     * Returns the provider's name.
     *
     * @return string
     */
    function getName();
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
