<?php

/**
 * This file is part of the SmsSender package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace SmsSender\HttpAdapter;

/**
 * @author Kévin Gomez <kevin_gomez@carpe-hora.com>
 */
interface HttpAdapterInterface
{
    /**
     * Returns the content fetched from a given URL.
     *
     * @param string $url     URL.
     * @param string $method  HTTP method to use for the request.
     * @param array  $headers Additionnal headers to send with the request.
     *                        The headers array should look like this:
     *                          array(
     *                            'Content-type: text/plain',
     *                            'Content-length: 100'
     *                          )
     * @param array  $data    The data to send when doing non "get" requests.
     * @return string
     */
    function getContent($url, $method = 'GET', array $headers = array(), array $data = array());

    /**
     * Returns the name of the HTTP Adapter.
     *
     * @return string
     */
    function getName();
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
