<?php

/**
 * This file is part of the SmsSender package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace SmsSender\Exception;

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class UnsupportedException extends \InvalidArgumentException implements Exception
{
    public function serialize()
    {
        return serialize(array($this->message, $this->code));
    }

    public function unserialize($serialized)
    {
        list($this->message, $this->code) = unserialize($serialized);
    }
}
