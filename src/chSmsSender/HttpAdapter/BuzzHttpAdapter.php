<?php

/**
 * This file is part of the chSmsSender package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace chSmsSender\HttpAdapter;

use Buzz\Browser;

/**
 * @author Kévin Gomez <kevin_gomez@carpe-hora.com>
 */
class BuzzHttpAdapter extends AbstractHttpAdapter implements HttpAdapterInterface
{
    /**
     * @var \Buzz\Browser
     */
    protected $browser;

    /**
     * @param \Buzz\Browser $browser
     */
    public function __construct(Browser $browser = null)
    {
        if (null === $browser) {
            $this->browser = new Browser();
        } else {
            $this->browser = $browser;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getContent($url, $method = 'GET', array $headers = array(), array $data = array())
    {
        $data = $this->encodePostData($data);

        try {
            $response = $this->browser->call($url, $method, $headers, $data);
        } catch (\Exception $e) {
            return null;
        }

        return $response ? $response->getContent() : null;;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'buzz';
    }
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
