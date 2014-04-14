<?php

/**
 * This file is part of the SmsSender package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace SmsSender;

use SmsSender\Exception\Exception;
use SmsSender\Exception\WrappedException;
use SmsSender\Provider\ProviderInterface;
use SmsSender\Result\ResultInterface;
use SmsSender\Result\Sms;

/**
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
class SmsSender implements SmsSenderInterface
{
    /**
     * Version
     */
    const VERSION = '1.1.1-dev';

    /**
     * @var array
     */
    protected $providers = array();

    /**
     * @var \SmsSender\Provider\ProviderInterface
     */
    protected $provider = null;

    /**
     * @param \SmsSender\Provider\ProviderInterface $provider
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
            return $this->transformResult(array(
                'status' => ResultInterface::STATUS_FAILED,
            ));
        }

        try {
            $data = $this->getProvider()->send($recipient, $body, $originator);
        } catch (Exception $e) {
            throw new WrappedException($e, array('recipient' => $recipient, 'body' => $body, 'originator' => $originator));
        }

        return $this->transformResult($data);
    }

    /**
     * Registers a provider.
     *
     * @param  \SmsSender\Provider\ProviderInterface $provider
     * @return \SmsSender\SmsSenderInterface
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
     * @param  array                         $providers
     * @return \SmsSender\SmsSenderInterface
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
     * @param  string                        $name A provider's name
     * @return \SmsSender\SmsSenderInterface
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
     * @return \SmsSender\Provider\ProviderInterface
     */
    public function getProvider()
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
     * @param  array                 $data An array of data.
     * @return \SmsSender\Result\Sms
     */
    protected function transformResult(array $data = array())
    {
        $result = new Sms();
        $result->fromArray($data);

        return $result;
    }
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
