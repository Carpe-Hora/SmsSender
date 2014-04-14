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
class WrappedException extends \Exception implements Exception
{
    protected $smsDetails = array();
    protected $wrapped;

    public function __construct(Exception $exception, array $smsDetails = array())
    {
        parent::__construct('', 0);

        $this->smsDetails = $smsDetails;
        $this->wrapped = $exception;
    }

    public function getSms()
    {
        return $this->smsDetails;
    }

    public function getWrappedException()
    {
        return $this->wrapped;
    }

    public function serialize()
    {
        return serialize(array($this->message, $this->code, $this->wrapped, $this->smsDetails));
    }

    public function unserialize($serialized)
    {
        list($this->message, $this->code, $this->wrapped, $this->smsDetails) = unserialize($serialized);
    }
}
