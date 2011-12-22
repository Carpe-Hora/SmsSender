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
     * @var string
     */
    protected $recipient = null;

    /**
     * @var string
     */
    protected $body = null;


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
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * {@inheritDoc}
     */
    public function getBody()
    {
        return $this->body;
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

        if (isset($data['recipient'])) {
            $this->recipient = (string) $data['recipient'];
        }

        if (isset($data['body'])) {
            $this->body = (string) $data['body'];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return array(
            'id'        => $this->id,
            'sent'      => $this->sent,
            'recipient' => $this->recipient,
            'body'      => $this->body,
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
