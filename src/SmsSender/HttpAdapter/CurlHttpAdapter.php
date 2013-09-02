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
class CurlHttpAdapter extends AbstractHttpAdapter implements HttpAdapterInterface
{
    /**
     * {@inheritDoc}
     */
    public function getContent($url, $method = 'GET', array $headers = array(), array $data = array())
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('cURL has to be enabled.');
        }

        $c = curl_init();

        // build the request...
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1 );  // allow redirects.
        curl_setopt($c, CURLOPT_CUSTOMREQUEST, strtoupper($method)); // define the HTTP method

        // join the data
        if (!empty($data)) {
          curl_setopt($c, CURLOPT_POSTFIELDS, $this->encodePostData($data));
        }

        // and add the headers
        curl_setopt($c, CURLOPT_HTTPHEADER, $headers);

        // execute the request
        $content = curl_exec($c);

        curl_close($c);

        if (false === $content) {
            $content = null;
        }

        return $content;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'curl';
    }
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
