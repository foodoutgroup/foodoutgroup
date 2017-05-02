<?php

namespace Food\AppBundle\Service\Mail;

use Symfony\Component\DependencyInjection\ContainerAware;

class MailerliteService extends ContainerAware
{
    private $fromName;

    private $fromEmail;

    private $variables = array();

    private $id;

    private $recipientEmail;

    private $recipientName;

    private $type;

    private $language;

    private $batchRecipients;

    private $attachments;

    private $replyToEmail;

    private $replyToName;

    private $isTest = 0;

    function __construct($api_key)
    {
        $this->apiKey = $api_key;
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }


    public function setTest()
    {

        $this->isTest = 1;

        return $this;
    }

    public function setRecipient($email, $name = '')
    {

        $this->recipientEmail = $email;
        $this->recipientName = $name;

        return $this;
    }

    public function setFromName($name)
    {

        $this->fromName = $name;

        return $this;
    }

    public function setFromEmail($email)
    {

        $this->fromEmail = $email;

        return $this;

    }

    public function setVariables($variables)
    {

        if (is_array($variables)) {
            foreach ($variables as $name => $value) {
                $this->setVariable($name, $value);
            }
        }

        return $this;
    }

    public function resetVariables()
    {
        $this->variables = array();

        return $this;
    }

    public function setVariable($name, $value)
    {

        $this->variables[$name] = $value;

        return $this;
    }

    public function setId($id)
    {

        $this->id = $id;

        return $this;
    }

    public function setType($type)
    {

        $this->type = $type;

        return $this;
    }

    public function setLanguage($code)
    {

        $this->language = $code;

        return $this;
    }

    public function addRecipient($recipient)
    {

        if (isset($recipient['email'])) {

            $recipient['recipientEmail'] = $recipient['email'];

            unset($recipient['email']);
        }

        if (isset($recipient['name'])) {

            $recipient['recipientName'] = $recipient['name'];

            unset($recipient['name']);
        }

        $this->batchRecipients[] = $recipient;

        return $this;
    }

    public function addRecipients($recipients)
    {

        foreach ($recipients as $recipient) {
            $this->addRecipient($recipient);
        }

        return $this;
    }

    public function setReplyTo($email, $name = '')
    {

        $this->replyToEmail = $email;
        $this->replyToName = $name;

        return $this;
    }

    public function addAttachment($filename, $content)
    {

        $this->attachments[] = array('name' => $filename, 'data' => base64_encode($content));

        return $this;
    }

    public function removeAttachments()
    {
        $this->attachments = array();

        return $this;
    }

    /**
     * {
     * "baseData":{
     * "from":"info@foodout.lt",
     * "fromTitle":"Foodout",
     * "subject":"test",
     * "templateSlug":"template-1489653773"
     * },
     * "recipients":[
     * {
     * "to":"daugirdas@foodout.lt",
     * "replacements":{
     * "maisto_ruosejas":"Čili",
     * "uzsakymas":"123321",
     * "itm_name":"Patiekalo pav.",
     * "itm_sum":"Patiekalo suma.",
     * "itm_amount":"Patiekalo kiekis.",
     * "itm_price":"Patiekalo kaina.",
     * "total_delivery":"<span style='color:green;'>123</span>"
     * }
     * }
     * ]
     * }
     */
    public function send()
    {
//        $data = $this->getTemplate();
//        $data = json_decode($data);
//        $loader = new \Twig_Loader_Array(array(
//            'body' => $data->data->body->html,
//        ));
//        $twig = $this->container->get('twig');
//        $oldLoader = $twig->getLoader();
//        $twig->setLoader($loader);
//
//        $data = array(
//            'appId' => $this->apiKey,
//            'data' => json_encode([
//                'baseData' => [
//                    'from' => $this->fromEmail,
//                    'fromTitle' => $this->fromName,
//                    'subject' => $data->data->subject,
//                    'bodyHtml' => $twig->render('body', $this->variables)
//                ],
//                'attachments' => $this->attachments,
//                'recipients' => [[
//                    'to' => $this->recipientEmail
//                ]]
//            ])
//        );
//
//        $ch = curl_init('http://api.mailerlite.com/api/v2/campaigns');
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($ch, CURLOPT_POST, true);
//        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
//        $output = curl_exec($ch);
//        curl_close($ch);
//
//        $twig->setLoader($oldLoader);
//
//        return $output;
    }

    public function getTemplate()
    {
//        $data = array(
//            'appId' => $this->apiKey,
//            'data' => json_encode([
//                'slug' => $this->id
//            ])
//        );
//        $ch = curl_init('http://api.mailerlite.com/api/v2/');
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($ch, CURLOPT_POST, mailerlite_api_keytrue);
//        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
//        $output = curl_exec($ch);
//        curl_close($ch);
//
//        return $output;
    }

    public function addAll($subscribers, $resubscribe = 0)
    {
        $curl = curl_init();

        $subscribers['subscribers'] = $subscribers;

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.mailerlite.com/api/v2/groups/" . $this->id . "/subscribers/import",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($subscribers),
            CURLOPT_HTTPHEADER => array(
                "content-type: application/json",
                "x-mailerlite-apikey: " . $this->apiKey
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            echo $response;
        }


    }
}
