<?php
namespace Api\BaseBundle\Helper;

use Api\BaseBundle\Response\ApplicationXML;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ResponseInterpreter extends Response
{

    public function __construct(Request $request, $data)
    {
        parent::__construct('', 200, []);

        switch ($request->headers->get('Content-Type')) {
            case "application/xml":
            case "text/xml":
                $this->headers->set('Content-Type', 'application/xml'); // todo ispresti sita per vidine klase
                $content = new ApplicationXML($data);
                break;
            default:
                $content = new JsonResponse($data);
                $this->headers->set('Content-Type', 'application/json'); // todo ispresti sita per vidine klase

                break;
        }


        $this->setContent($content->getContent());
        
    }

}