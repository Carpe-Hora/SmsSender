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
 * @author Kévin Gomez <contact@kevingomez.fr>
 */
abstract class AbstractProvider implements ProviderInterface
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

    /**
     * Validate an originator string
     *
     * If the originator ('from' field) is invalid, some networks may reject
     * the network whilst stinging you with the financial cost! While this
     * cannot correct them, it will try its best to correctly format them.
     *
     * @param string $number The phone number to clean.
     *
     * @return string The cleaned phone number.
     */
    protected function cleanOriginator($number)
    {
        // Remove any invalid characters
        $ret = preg_replace('/[^a-zA-Z0-9]/', '', (string) $number);

        if (preg_match('/[a-zA-Z]/', $number)) {
            // Alphanumeric format so make sure it's < 11 chars
            $ret = substr($ret, 0, 11);
        } else {
            // Numerical, remove any prepending '00'
            if (substr($ret, 0, 2) == '00') {
                $ret = substr($ret, 2);
                $ret = substr($ret, 0, 15);
            }
        }

        return (string) $ret;
    }

    /**
     * Checks if the given string contains unicode characters.
     *
     * @param string $string The string to test.
     *
     * @return bool
     * @author Kevin Gomez <contact@kevingomez.fr>
     */
    protected function containsUnicode($string)
    {
        return max(array_map('ord', str_split($string))) > 127;
    }

    /**
     * Fix a local phone number to transform it to its equivalent international
     * format.
     *
     * @param string $number The phone number to fix.
     * @param string $prefix The prefix to use.
     *
     * @return string The fixed phone number;
     * @author Kevin Gomez <contact@kevingomez.fr>
     */
    protected function localNumberToInternational($number, $prefix)
    {
        // already international format
        if ($number[0] === '+') {
            return $number;
        }

        // remove the leading 0 and add the prefix
        return sprintf('%s%s', $prefix, substr($number, 1));
    }
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
