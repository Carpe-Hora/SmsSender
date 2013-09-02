<?php

/**
 * This file is part of the SmsSender package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace SmsSender\Provider;

use SmsSender\HttpAdapter\HttpAdapterInterface;
use SmsSender\Result\ResultInterface;

/**
 * @author Kévin Gomez <kevin_gomez@carpe-hora.com>
 */
abstract class AbstractProvider
{
    /**
     * @var \SmsSender\HttpAdapter\HttpAdapterInterface
     */
    protected $adapter = null;

    /**
     * @param \SmsSender\HttpAdapter\HttpAdapterInterface $adapter An HTTP adapter.
     */
    public function __construct(HttpAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Returns the HTTP adapter.
     *
     * @return \SmsSender\HttpAdapter\HttpAdapterInterface
     */
    protected function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Default values for "send" queries.
     *
     * @return array
     */
    protected function getDefaults()
    {
        return array(
            'id'     => null,
            'status' => ResultInterface::STATUS_FAILED
        );
    }
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
