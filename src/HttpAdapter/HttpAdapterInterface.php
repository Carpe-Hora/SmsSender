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
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
interface HttpAdapterInterface
{
    /**
     * Returns the content fetched from a given URL.
     *
     * @param  string $url     URL.
     * @param  string $method  HTTP method to use for the request.
     * @param  array  $headers Additionnal headers to send with the request.
     *                         The headers array should look like this:
     *                         array(
     *                              'Content-type: text/plain',
     *                              'Content-length: 100'
     *                         )
     * @param  mixed  $data    The data to send when doing non "get" requests.
     *                         Gets sent as POST parameters if a key/value
     *                         array is passed or as request body if a
     *                         string is passed.
     *
     * @return string
     */
    public function getContent($url, $method = 'GET', array $headers = array(), $data = array());

    /**
     * Returns the name of the HTTP Adapter.
     *
     * @return string
     */
    public function getName();

    /**
     * Return last request as string or object
     *
     * @param bool|false $string
     *
     * @return mixed|string
     */
    public function getLastRequest($string = false);
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
