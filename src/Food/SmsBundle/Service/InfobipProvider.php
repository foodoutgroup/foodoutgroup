<?php

namespace Food\SmsBundle\Service;
use Food\SmsBundle\Exceptions\ParseException;

/**
 * Class InfobipProvider
 * @package Food\SmsBundle\Service
 *
 * @examples
 * http://api2.infobip.com/api/command?username=skanu1&password=119279&cmd=CREDITS
 * $infobipProvider->authenticate('skanu1', '119279');
 * $infobipProvider->setApiUrl('http://api.infobip.com/api/v3/sendsms/json');
 */
class InfobipProvider implements SmsProviderInterface {

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
     * @var null
     */
    private $logger = null;

    /**
     * @var bool
     */
    private $debugEnabled = false;

    /**
     * InfoBip sending error statuses. Add here as they update
     * @var array
     *
     * http://www.infobip.com/themes/site_themes/infobip/documentation/Infobip_HTTP_API_and_SMPP_specification.pdf
     */
    private $errorStatuses = array(
        '-1' => 'SEND_ERROR',
        '-2' => 'NOT_ENOUGH_CREDITS',
        '-3' => 'NETWORK_NOTCOVERED',
        '-5' => 'INVALID_USER_OR_PASS',
        '-6' => 'MISSING_DESTINATION_ADDRESS',
        '-10' => 'MISSING_USERNAME',
        '-11' => 'MISSING_PASSWORD',
        '-13' => 'INVALID_DESTINATION_ADDRESS',
        '-22' => 'SYNTAX_ERROR',
        '-23' => 'ERROR_PROCESSING',
        '-26' => 'COMMUNICATION_ERROR',
        '-27' => 'INVALID_SENDDATE',
        '-28' => 'INVALID_DELIVERY_REPORT_PUSH_URL',
        '-30' => 'INVALID_CLIENT_APPID',
        '-33' => 'DUPLICATE_MESSAGEID',
        '-34' => 'SENDER_NOT_ALLOWED',
        '-99' => 'GENERAL_ERROR',
    );

    /**
     * Message states, assigned by InfoBip when message is delivered
     * @var array
     */
    private $deliveredStates = array(
        'SENT',
        'DELIVERED',
    );

    /**
     * Undelivered message state, assigned by InfoBip
     * @var array
     */
    private $undeliveredStates = array(
        'NOT_SENT',
        'NOT_DELIVERED',
        'NOT_ALLOWED',
        'INVALID_DESTINATION_ADDRESS',
        'INVALID_SOURCE_ADDRESS',
        'ROUTE_NOT_AVAILABLE',
        'NOT_ENOUGH_CREDITS',
        'REJECTED',
        'INVALID_MESSAGE_FORMAT',
    );
    /**
     * @var null
     */
    private $login;

    /**
     * @param string $url
     * @param string $accoutApiUrl
     * @param string $login
     * @param string $password
     * @param Logger $logger
     */
    public function __construct($url=null, $accoutApiUrl=null, $login=null, $password=null, $logger=null)
    {
        $this->apiUrl = $url;
        $this->accountApiUrl = $accoutApiUrl;
        $this->logger = $logger;
        $this->login = $login;
        $this->password = $password;
    }

    /**
     * @param null $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return null
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
        $this->log('++ infobip call ++');
        $requestData = array(
            'authentication' => array(
                'username' => $this->username,
                'password' => $this->password,
            ),
            'messages' => $data
        );

        $this->log('-- sending request withg data: '.var_export($requestData, true));
        $this->log('-- sending data in json: '.json_encode($requestData));
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->apiUrl);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, 'JSON='.json_encode($requestData));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);

        $this->log('-- direct response from infobip: '.var_export($response, true));

        if (curl_errno($curl)) {
            // TODO kitas exception tipas
            $this->log('-- Got error connecting infobip: '.curl_error($curl));
            new \InvalidArgumentException('Http connection error: '.curl_error($curl));
        } else {
            curl_close($curl);
        }

        return $response;
    }

    /**
     * TODO
     * @param $response
     * @throws \Food\SmsBundle\Exceptions\ParseException
     * @return mixed
     */
    public function parseResponse($response)
    {
        $this->log('++ infobip parseResponse ++');
        $decodedResponse = json_decode($response, true);
        $jsonErr = json_last_error();

        $this->log('-- decoded response: '.var_export($decodedResponse, true));
        $this->log('-- json error? '.var_export($jsonErr, true));

        if ($jsonErr != JSON_ERROR_NONE) {
            throw new ParseException('JSON decode error: '.$jsonErr);
        }

        if (!isset($decodedResponse['results']) || empty($decodedResponse['results'])) {
            throw new ParseException('Unknown InfoBip response format. Am I out of my mind?');
        }

        $parsedResponse = array();

        foreach ($decodedResponse['results'] as $messageStatus) {
            // Something wrong happened
            if ($messageStatus['status'] < 0) {
                $messageStatus['sent'] = false;
                if (isset($this->errorStatuses[$messageStatus['status']])) {
                    $messageStatus['error'] = $this->errorStatuses[$messageStatus['status']];
                } else {
                    $messageStatus['error'] = 'Unknown error returned from InfoBip. Error status: '.$messageStatus['status'];
                }
            } else {
                $messageStatus['sent'] = true;
                $messageStatus['error'] = null;
            }
            $parsedResponse[] = $messageStatus;
        }

        $this->log('-- Parsed response: '.var_export($parsedResponse, true));

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
        $this->log("++ infobip sendMessage ++");
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

        try {
            $response = $this->call(
                array(
                    array(
                        'sender' => $sender,
                        'text' => $message,
                        'recipients' =>
                            array(
                                array('gsm' => $recipient),
                            ),
                    )
                )
            );

            $this->log("-- Got response: ".var_export($response, true));
            $parsedResponse = $this->parseResponse($response);
            $messageStatus = array_shift($parsedResponse);

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
     * @param $dlrData
     *
     * @return array
     */
    public function parseDeliveryReport($dlrData)
    {
        $parsedMessages = array();

        $dom = new \DOMDocument();
        $dom->loadXML($dlrData);
        $xPath = new \domxpath($dom);
        $reports = $xPath->query("/DeliveryReport/message");

        if (!empty($reports)) {
            foreach ($reports as $node) {
                $message = array(
                    'extId' => $node->getAttribute('id'),
                    'sendDate' => $node->getAttribute('sentdate'),
                    'completeDate' => $node->getAttribute('donedate'),
                );

                $message['sendDate'] = date("Y-m-d H:i:s", strtotime($message['sendDate']));
                $message['completeDate'] = date("Y-m-d H:i:s", strtotime($message['completeDate']));

                $infoBipStatus = $node->getAttribute('status');
                $gsmErrorCode = $node->getAttribute('gsmerror');

                if ($this->isDeliveredStatus($infoBipStatus)) {
                    $message['delivered'] = true;
                    $message['error'] = null;
                } else if ($this->isUndeliveredStatus($infoBipStatus)) {
                    $message['delivered'] = false;
                    $message['error'] = $infoBipStatus;

                    if (!empty($gsmErrorCode) && $gsmErrorCode > 0) {
                        $message['error'] .= ' GSM Error code: '.$gsmErrorCode;
                    }
                } else {
                    $message['delivered'] = false;
                    $message['error'] = 'Infobip returned unknown status: '.$infoBipStatus;
                }

                $parsedMessages[] = $message;
            }
        }

        return $parsedMessages;
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
            '%s/command?username=%s&password=%s&cmd=CREDITS',
            $this->accountApiUrl,
            $this->username,
            $this->password
        );

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $balanceUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $balance = curl_exec($curl);

        if (curl_errno($curl)) {
            // TODO kitas exception tipas
            new \InvalidArgumentException('Connection error: '.curl_error($curl));
        } else {
            curl_close($curl);
        }

        return (float)$balance;
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
     * Determines by InfoBip status if message is delivered or not
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
     * Determines by InfoBip status if message is delivered or not
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
        return 'InfoBip';
    }
}