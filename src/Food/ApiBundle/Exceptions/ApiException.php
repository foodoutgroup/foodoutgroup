<?php
namespace Food\ApiBundle\Exceptions;

use Exception;

class ApiException extends \Exception
{
    /**
     * @var int
     */
    private $statusCode = 400;

    /**
     * @var array
     */
    private $errorData = array(
        'error' => null,
        'description' => null,
    );

    public function __construct($message = "", $statusCode = 400, $errorData = array(), $code = 0, Exception $previous = null)
    {
        $this->setStatusCode($statusCode);
        $this->setErrorData($errorData);

        parent::__construct($message, $code, $previous);
    }


    /**
     * @param int $statusCode
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param array $errorData
     */
    public function setErrorData($errorData)
    {
        $this->errorData = $errorData;
    }

    /**
     * @return array
     */
    public function getErrorData()
    {
        return $this->errorData;
    }

}
