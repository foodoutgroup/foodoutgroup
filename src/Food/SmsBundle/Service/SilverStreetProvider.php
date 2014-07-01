<?php

namespace Food\SmsBundle\Service;

use Food\SmsBundle\Exceptions\ParseException;
use Curl;

/**
 * Class SilverStreetProvider
 * @package Food\SmsBundle\Service
 *
 * @examples
 * http://api.silverstreet.com/send.php?username=test&password=test&destination=31134690886&sender=silver&body=This%20is%20a%test%20message
 * $silverStreetProvider->authenticate('login', 'password');
 * $silverStreetProvider->setApiUrl('http://api.silverstreet.com/send.php');
 */
class SilverStreetProvider implements SmsProviderInterface {

    /**
     * @var string
     */
    private $username = null;

    /**
     * @var string
     */
    private $password = null;

    /**
     * @var string
     */
    private $apiUrl = null;

    /**
     * @var string
     */
    private $accountApiUrl = null;

    /**
     * @var object|null
     */
    private $logger = null;

    /**
     * @var bool
     */
    private $debugEnabled = false;

    /**
     * Silverstreet sending error statuses. Add here as they update
     * @var array
     */
    private $errorStatuses = array(
        '100' => 'PARAMETERS_MISSING',
        '110' => 'BAD_PARAMETERS_COMBINATION',
        '120' => 'INVALID_PARAMETERS',
        '130' => 'INSUFFICIENT_CREDITS',
    );

    /**
     * Silverstreet error reason codes to text
     * @var array
     */
    private $reasonStatuses = array(
        '1' => 'Absent subscriber (network cannot contact recipient)',
        '2' => 'Handset memory exceeded',
        '3' => 'Equipment protocol error',
        '5' => 'Unknown service centre (unknown Destination Operator)',
        '6' => 'Service centre congestion (congestion at Destination Operator)',
        '9' => 'Unknown subscriber (Recipient number is unknown in the HLR)',
        '10' => 'Illegal subscriber (The mobile station failed authentication) ',
        '12' => 'Illegal equipment (Recipient number check failed, blacklisted or not whitelisted)',
        '13' => 'Call barred (Operator barred the recipient number)',
        '16' => 'System failure',
        '20' => 'Resource limitation at Recipient or Destination Operator',
        '30' => 'Unidentified recipient',
        '31' => 'Service temporary not available ',
        '32' => 'Illegal error code',
        '33' => 'Network timeout',
        '35' => 'Delivery failed ',
        '36' => 'Error in mobile station',
        '43' => 'Subscriber temporarily unreachable (While roaming)',
        '46' => 'Closed user group reject',
        '47' => 'Network failure',
        '48' => 'Deferred Delivery (Message has not been delivered and is part of a deferred delivery schedule)',
        '50' => 'Insufficient credit',
        '51' => 'Rejected Destination',
        '52' => 'Rejected Unknown Reason',
        '54' => 'Rejected due to blocking issue',
        '56' => 'Rejected due to not enough credits',
        '57' => 'Rejected due to spam filter',
        '58' => 'Rejected due to flooding ',
        '66' => 'Error in SMSC',
        '67' => 'Rejected by operator due to validity period expiry',
        '68' => 'Intermediate state notification that the message has not yet been delivered due to a phone related problem
but is being retried.',
        '69' => 'Cannot determine whether this message has been delivered or has failed due to lack of final delivery state
information from the operator.',
        '87' => 'Short Term Denial',
    );

    /**
     * Message states, assigned by Silverstreet when message is delivered
     * @var array
     */
    private $deliveredStates = array(
        'Delivered',
    );

    /**
     * Undelivered message state, assigned by Silverstreet
     * @var array
     *
     * https://portal.silverstreet.com/support/documents/download_file/6
     */
    private $undeliveredStates = array(
        'Not Delivered',
        'Buffered',
    );

    /**
     * @var Curl
     */
    private $_cli;

    /**
     * @param string $url
     * @param string $accoutApiUrl
     * @param string $username
     * @param string $password
     * @param Logger $logger
     */
    public function __construct($url=null, $accoutApiUrl=null, $username=null, $password=null, $logger=null)
    {
        $this->apiUrl = $url;
        $this->accountApiUrl = $accoutApiUrl;
        $this->logger = $logger;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @param object|null $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return \Monolog\Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return bool
     */
    public function isDebugEnabled()
    {
        return $this->debugEnabled;
    }

    /**
     * @param bool $state
     */
    public function setDebugEnabled($state)
    {
        $this->debugEnabled = $state;
    }

    /**
     * @param \Curl $cli
     */
    public function setCli($cli)
    {
        $this->_cli = $cli;
    }

    /**
     * @return \Curl
     */
    public function getCli()
    {
        if (empty($this->_cli)) {
            $this->_cli = new Curl;
            $this->_cli->options['CURLOPT_SSL_VERIFYPEER'] = false;
            $this->_cli->options['CURLOPT_SSL_VERIFYHOST'] = false;
        }
        return $this->_cli;
    }

    public function log($message)
    {
        if ($this->isDebugEnabled() && !empty($this->logger)) {
            $this->logger->debug($message);
        }
    }

    /**
     * @param string $username
     * @param string $password
     * @throws \InvalidArgumentException
     */
    public function authenticate($username, $password)
    {
        if (empty($username) || empty($password)) {
            throw new \InvalidArgumentException('Username or password for messaging platform not given');
        }
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @param array $data
     * @return mixed
     */
    protected function call($data)
    {
        $this->log('++ Silverstreet call ++');
        $requestData = array(
            'username' => $this->username,
            'password' => $this->password,
            'dlr' => 1,
        );

        $requestData = array_merge($requestData, $data);

        $this->log('-- sending request withg data: '.var_export($requestData, true));

        $resp = $this->getCli()->get(
            $this->apiUrl,
            $requestData
        );

        return $resp->body;
    }

    /**
     * Parse Silverstreet response for message sending
     *
     * @param $response
     * @return array
     */
    public function parseResponse($response)
    {
        $this->log('++ Silverstreet parseResponse ++');

        $response = (int)$response;

        $parsedResponse = array();

        if ($response != 1) {
            $parsedResponse['sent'] = false;
            if (isset($this->errorStatuses[$response])) {
                $parsedResponse['error'] = $this->errorStatuses[$response];
            } else {
                $parsedResponse['error'] = 'Unknown error returned from Silverstreet. Error status: '.$response;
            }
        } else {
            $parsedResponse['sent'] = true;
            $parsedResponse['error'] = null;
        }

        return $parsedResponse;
    }

    /**
     * @param $sender
     * @param $recipient
     * @param $message
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @return mixed
     */
    public function sendMessage($sender, $recipient, $message)
    {
        $this->log("++ Silverstreet sendMessage ++");
        $this->log(
            sprintf("-- Siuntejas: %s | Gavejas: %s | Zinute: %s", $sender, $recipient, $message)
        );
        if (empty($sender)) {
            throw new \InvalidArgumentException('Sender not given');
        }

        if (empty($recipient)) {
            throw new \InvalidArgumentException('Recipient not given');
        }

        if (empty($message)) {
            throw new \InvalidArgumentException('Message not given');
        }

        $reference = 'sil'
            .md5(
            'r='.$recipient.'t='.$message.date("YmdHis")
        );

        try {
            $response = $this->call(
                array(
                    'sender' => $sender,
                    'body' => $message,
                    'destination' => $recipient,
                    'reference' => $reference
                )
            );

            $this->log("-- Got response: ".var_export($response, true));
            $parsedResponse = $this->parseResponse($response);
            $messageStatus = $parsedResponse;
            $messageStatus['messageid'] = $reference;

        // TODO Noramlus exception handlingas cia ir servise (https://basecamp.com/2470154/projects/4420182-skanu-lt-gamyba/todos/73047842-pilnas-exception)
        } catch (\Exception $e) {
            $this->log('Exception occured: '.$e->getMessage());
            throw $e;
        }
        return $messageStatus;
    }

    /**
     * @param $sender
     * @param $recipients
     * @param $message
     * @throws \Exception
     */
    public function sendMultipleMessages($sender, $recipients, $message)
    {
        // TODO: Implement sendMultipleMessages() method.
        throw new \Exception('Please, be so kind - implement me!');
    }

    /**
     * @param string|array $dlrData
     *
     * @return array
     */
    public function parseDeliveryReport($dlrData)
    {
        $message = array();

        if (!empty($dlrData) && isset($dlrData['reference'])) {
            $message = array(
                'extId' => $dlrData['reference'],
                'sendDate' => null,
                'completeDate' => $dlrData['timestamp'],
            );

            $message['completeDate'] = date("Y-m-d H:i:s", strtotime('+1 hour', strtotime($message['completeDate'])));

            $silverstreetStatus = $dlrData['status'];

            if ($this->isDeliveredStatus($silverstreetStatus)) {
                $message['delivered'] = true;
                $message['error'] = null;
            } else if ($this->isUndeliveredStatus($silverstreetStatus)) {
                $message['delivered'] = false;

                if (!empty($dlrData['reason'])) {
                    $reason = $this->getReasonFromCode($dlrData['reason']);
                    if (empty($reason)) {
                        $reason = "unknown reason code: ".$dlrData['reason'];
                    }
                } else {
                    $reason = 'no reason';
                }
                $message['error'] = 'Silverstreet undelivered due to: '.$reason;
            } else {
                $message['delivered'] = false;
                $message['error'] = 'Silverstreet returned unknown status: '.$silverstreetStatus;
            }
        }

        return array($message);
    }

    /**
     * @return float
     * @throws \InvalidArgumentException
     */
    public function getAccountBalance()
    {
        if (empty($this->accountApiUrl)) {
            throw new \InvalidArgumentException('Account API url not set');
        }
        if (empty($this->username) || empty($this->password)) {
            throw new \InvalidArgumentException('Username or password to Account API not given');
        }

        $balanceUrl = sprintf(
            '%s?username=%s&password=%s',
            $this->accountApiUrl,
            $this->username,
            $this->password
        );

        $resp = $this->getCli()->get($balanceUrl);

        $dom = new \DOMDocument();
        $dom->loadXML($resp->body);
        $balanceElement = $dom->getElementsByTagName('balance');
        return (float)$balanceElement->item(0)->nodeValue;
    }

    /**
     * @param string $url
     */
    public function setApiUrl($url)
    {
        $this->apiUrl = $url;
    }

    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    /**
     * @param $status
     *
     * @return null|string
     */
    public function getErrorFromStatus($status)
    {
        if (!isset($this->errorStatuses[$status])) {
            return null;
        } else {
            return $this->errorStatuses[$status];
        }
    }

    /**
     * @param int $reasonCode
     *
     * @return null|string
     */
    public function getReasonFromCode($reasonCode)
    {
        if (!isset($this->reasonStatuses[$reasonCode])) {
            return null;
        } else {
            return $this->reasonStatuses[$reasonCode];
        }
    }

    /**
     * Determines by Silverstreet status if message is delivered or not
     *
     * @param string $status
     * @return bool
     */
    public function isDeliveredStatus($status)
    {
        if (in_array($status, $this->deliveredStates)) {
            return true;
        }
        return false;
    }

    /**
     * Determines by Silverstreet status if message is delivered or not
     *
     * @param string $status
     * @return bool
     */
    public function isUndeliveredStatus($status)
    {
        if (in_array($status, $this->undeliveredStates)) {
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    public function getProviderName()
    {
        return 'Silverstreet';
    }
}