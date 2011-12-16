<?php

/**
 * This file is part of the chSmsSender package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace chSmsSender\Provider;

use chSmsSender\HttpAdapter\HttpAdapterInterface;

/**
 * @author Kévin Gomez <kevin_gomez@carpe-hora.com>
 */
abstract class AbstractProvider
{
    /**
     * @var \chSmsSender\HttpAdapter\HttpAdapterInterface
     */
    protected $adapter = null;


    /**
     * @param \chSmsSender\HttpAdapter\HttpAdapterInterface $adapter   An HTTP adapter.
     */
    public function __construct(HttpAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Returns the HTTP adapter.
     *
     * @return \chSmsSender\HttpAdapter\HttpAdapterInterface
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
            'sent'   => null,
        );
    }
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
