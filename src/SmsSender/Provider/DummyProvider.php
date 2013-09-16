<?php

/**
 * This file is part of the SmsSender package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace SmsSender\Provider;

use SmsSender\Provider\ProviderInterface;
use SmsSender\Result\ResultInterface;

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class DummyProvider implements ProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function send($recipient, $body, $originator = '')
    {
        return array(
            'id'         => uniqid(),
            'recipient'  => $recipient,
            'body'       => $body,
            'originator' => $originator,
            'status'     => ResultInterface::STATUS_SENT,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'dummy';
    }
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
