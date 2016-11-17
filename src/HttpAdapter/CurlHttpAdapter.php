<?php

/**
 * This file is part of the SmsSender package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace SmsSender\HttpAdapter;

use SmsSender\Exception\AdapterException;

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class CurlHttpAdapter extends AbstractHttpAdapter implements HttpAdapterInterface
{
    /**
     * @var array
     */
    protected $lastRequest;

    /**
     * {@inheritDoc}
     */
    public function getContent($url, $method = 'GET', array $headers = array(), $data = array())
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('cURL has to be enabled.');
        }

        $c = curl_init();

        // build the request...
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1); // allow redirects.
        curl_setopt($c, CURLOPT_CUSTOMREQUEST, strtoupper($method)); // define the HTTP method

        // join the data
        if (!empty($data) && 'POST' === strtoupper($method)) {
            if (is_array($data)) {
                $data = $this->encodePostData($data);
            }
            curl_setopt($c, CURLOPT_POSTFIELDS, $data);
        }

        // and add the headers
        if (!empty($headers)) {
            curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
        }

        // execute the request
        $content = curl_exec($c);

        $this->setLastRequest([
            'url' => $url,
            'method' => $method,
            'headers' => $headers,
            'data' => $data,
        ]);

        if(curl_errno($c)){
            $adapterException = new AdapterException(curl_error($c), curl_errno($c));
            $adapterException->setData(curl_getinfo($c));
            curl_close($c);
            throw $adapterException;
        }
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

    /**
     * @param bool|false $string
     *
     * @return mixed|string
     */
    public function getLastRequest($string = false)
    {
        return ($string) ? json_encode($this->lastRequest) : $this->lastRequest;
    }

    /**
     * Return last request as string or object
     *
     * @param array $lastRequest
     */
    protected function setLastRequest($lastRequest)
    {
        $this->lastRequest = $lastRequest;
    }
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
