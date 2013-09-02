<?php

/**
 * This file is part of the SmsSender package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace SmsSender\Result;

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
     * @var string
     */
    protected $recipient = null;

    /**
     * @var string
     */
    protected $body = null;

    /**
     * @var string
     */
    protected $originator = null;

    /**
     * @var string
     */
    protected $status = null;

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
        return $this->status === ResultInterface::STATUS_DELIVERED
            || $this->status === ResultInterface::STATUS_SENT;
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
    public function getOriginator()
    {
        return $this->originator;
    }

    /**
     * {@inheritDoc}
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * {@inheritDoc}
     */
    public function fromArray(array $data = array())
    {
        if (!empty($data['id'])) {
            $this->id = (string) $data['id'];
        }

        if (isset($data['recipient'])) {
            $this->recipient = (string) $data['recipient'];
        }

        if (isset($data['body'])) {
            $this->body = (string) $data['body'];
        }

        if (isset($data['originator'])) {
            $this->originator = (string) $data['originator'];
        }

        if (isset($data['status'])) {
            if (!in_array($data['status'], $this->getValidStatus())) {
                throw new \RuntimeException(sprintf('Invalid status given: "%s"', $data['status']));
            }

            $this->status = (string) $data['status'];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return array(
            'id'         => $this->id,
            'recipient'  => $this->recipient,
            'body'       => $this->body,
            'originator' => $this->originator,
            'status'     => $this->status,
            'sent'       => $this->isSent(),
        );
    }

    /**
     * Returns a list of the valid status.
     *
     * @return array
     * @author Kevin Gomez <kevin_gomez@carpe-hora.com>
     */
    public function getValidStatus()
    {
        return array(
            ResultInterface::STATUS_SENT,
            ResultInterface::STATUS_DELIVERED,
            ResultInterface::STATUS_FAILED,
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
