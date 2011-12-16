<?php

/**
 * This file is part of the chSmsSender package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace chSmsSender\Result;

/**
 * @author Kévin Gomez <kevin_gomez@carpe-hora.com>
 */
class Sms implements ResultInterface, \ArrayAccess
{
    /**
     * @var string
     */
    protected $id = null;

    /**
     * @var boolean
     */
    protected $sent = null;


    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function isSent()
    {
        return $this->sent;
    }

    /**
     * {@inheritDoc}
     */
    public function fromArray(array $data = array())
    {
        if (!empty($data['id'])) {
            $this->id = (string) $data['id'];
        }
        if (isset($data['sent'])) {
            $this->sent = (bool) $data['sent'];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return array(
            'id'    => $this->id,
            'sent'  => $this->sent,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
        return property_exists($this, $offset) && null !== $this->$offset;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        $offset = strtolower($offset);

        return $this->offsetExists($offset) ? $this->$offset : null;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value)
    {
        $offset = strtolower($offset);
        if ($this->offsetExists($offset)) {
            $this->$offset = $value;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset)
    {
        $offset = strtolower($offset);
        if ($this->offsetExists($offset)) {
            $this->$offset = null;
        }
    }
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
