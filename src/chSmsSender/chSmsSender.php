<?php

/**
 * This file is part of the chSmsSender package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace chSmsSender;

use chSmsSender\Provider\ProviderInterface;
use chSmsSender\Result\Sms;

/**
 * @author Kévin Gomez <kevin_gomez@carpe-hora.com>
 */
class chSmsSender implements SmsSenderInterface
{
    /**
     * Version
     */
    const VERSION = '1.0.0';

    /**
     * @var array
     */
    protected $providers = array();

    /**
     * @var \chSmsSender\Provider\ProviderInterface
     */
    protected $provider = null;


    /**
     * @param \chSmsSender\Provider\ProviderInterface $provider
     */
    public function __construct(ProviderInterface $provider = null)
    {
        $this->provider = $provider;
    }

    /**
     * {@inheritDoc}
     */
    public function send($recipient, $body, $originator = '')
    {
        if (empty($recipient) || empty($body)) {
            // let's save a request
            return $this->returnResult(array());
        }

        $data   = $this->getProvider()->send($recipient, $body, $originator);
        $result = $this->returnResult($data);

        return $result;
    }

    /**
     * Registers a provider.
     *
     * @param \chSmsSender\Provider\ProviderInterface $provider
     * @return \chSmsSender\AbstractProvider
     */
    public function registerProvider(ProviderInterface $provider)
    {
        if (null !== $provider) {
            $this->providers[$provider->getName()] = $provider;
        }

        return $this;
    }

    /**
     * Registers a set of providers.
     *
     * @param array $providers
     * @return \chSmsSender\AbstractProvider
     */
    public function registerProviders(array $providers = array())
    {
        foreach ($providers as $provider) {
            $this->registerProvider($provider);
        }

        return $this;
    }

    /**
     * Sets the provider to use.
     *
     * @param string $name  A provider's name
     * @return \chSmsSender\AbstractProvider
     */
    public function using($name)
    {
        if (isset($this->providers[$name])) {
            $this->provider = $this->providers[$name];
        }

        return $this;
    }

    /**
     * Returns registered providers indexed by name.
     *
     * @return array
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * Returns the provider to use.
     *
     * @return \chSmsSender\Provider\ProviderInterface
     */
    protected function getProvider()
    {
        if (null === $this->provider) {
            if (0 === count($this->providers)) {
                throw new \RuntimeException('No provider registered.');
            } else {
                $this->provider = $this->providers[key($this->providers)];
            }
        }

        return $this->provider;
    }

    /**
     * @param array $data   An array of data.
     * @return \chSmsSender\Result\Sms
     */
    protected function returnResult(array $data = array())
    {
        $result = new Sms();
        $result->fromArray($data);

        return $result;
    }
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
