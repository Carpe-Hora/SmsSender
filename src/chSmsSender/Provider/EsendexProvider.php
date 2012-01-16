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
use chSmsSender\Provider\ProviderInterface;

/**
 * @author Kévin Gomez <kevin_gomez@carpe-hora.com>
 */
class EsendexProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * @var string
     */
    const SEND_SMS_URL = 'http://www.esendex.com/secure/messenger/formpost/SendSMS.aspx';

    /**
     * @var string
     */
    const SMS_STATUS_URL = 'http://www.esendex.com/secure/messenger/formpost/QueryStatus.aspx';

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $accountRef;


    /**
     * {@inheritDoc}
     */
    public function __construct(HttpAdapterInterface $adapter, $username, $password, $accountRef)
    {
        parent::__construct($adapter);

        $this->username = $username;
        $this->password = $password;
        $this->accountRef = $accountRef;
    }

    /**
     * {@inheritDoc}
     */
    public function send($recipient, $body, $originator = '')
    {
        if (null === $this->username || null === $this->password || null === $this->accountRef) {
            throw new \RuntimeException('No API credentials provided');
        }

        $params = $this->getParameters(array(
            'recipient'  => $recipient,
            'body'       => $body,
            'originator' => $originator,
            'type'       => 'Text',
        ));

        return $this->executeQuery(self::SEND_SMS_URL, $params, array(
            'recipient'  => $recipient,
            'body'       => $body,
            'originator' => $originator,
        ));
    }

    /**
     * Retrieves the status of a message
     *
     * @param string $messageId The message Id.
     * @return array
     */
    public function getStatus($messageId)
    {
        if (null === $this->username || null === $this->password || null === $this->accountRef) {
            throw new \RuntimeException('No API credentials provided');
        }

        $params = $this->getParameters(array(
            'messageID' => $messageId,
        ));

        return $this->executeQuery(self::SMS_STATUS_URL, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'esendex';
    }

    /**
     * @param string $query
     * @return array
     */
    protected function executeQuery($url, array $data = array(), array $extra_result_data = array())
    {
        $content = $this->getAdapter()->getContent($url, 'POST', $headers = array(), $data);

        if (null === $content) {
            return $this->getDefaults();
        }

        return $this->parseResults($content, $extra_result_data);
    }

    /**
     * Builds the parameters list to send to the API.
     *
     * @return array
     * @author Kevin Gomez <kevin_gomez@carpe-hora.com>
     */
    public function getParameters(array $additionnal_parameters = array())
    {
        return array_merge(array(
            'username'  => $this->username,
            'password'  => $this->password,
            'account'   => $this->accountRef,
            'plainText' => '1',
        ), $additionnal_parameters);
    }

    /**
     * Parse the data returned by the API.
     *
     * @param string $result The raw result string.
     * @return array
     */
    protected function parseResults($result, array $extra_result_data = array())
    {
        // the data sent by the API looks like this
        //  Result=OK
        //  MessageIDs=3c13bbba-a9c2-460c-961b-4d6772960af0

        $data = array();

        // split the lines
        $result_lines = explode("\n", $result);

        // handle the key <-> value pairs
        foreach ($result_lines as $line) {
            if (empty($line)) {
                continue;
            }

            $line_data = explode('=', $line);

            if (count($line_data) === 2) {
              $data[$line_data[0]] = trim(urldecode($line_data[1]));
            }
        }


        // and now, clean a bit the data
        if (isset($data['Result'])) {
            $data['sent'] = $data['Result'] == 'OK';
            unset($data['Success']);
        }

        if (isset($data['MessageIDs'])) {
            $data['id'] = $data['MessageIDs'];
            unset($data['MessageIDs']);
        }

        return array_merge($this->getDefaults(), $data, $extra_result_data);
    }
}

// vim: set softtabstop=4 tabstop=4 shiftwidth=4 autoindent:
