<?php

namespace Pirminis\Gateway\Swedbank\Banklink;

use Pirminis\Gateway\Swedbank\Banklink\Response;

class Form
{
    const REDIRECT_PARAMETER_XPATH = '//APMTxn//Purchase//RedirectParameters//RedirectParameter';

    protected $form_fields = array();
    protected $redirect_url;

    public function __construct($dom, $redirect_url)
    {
        $this->process_form_fields($dom);
        $this->redirect_url = $redirect_url;
    }

    public function form_fields()
    {
        return $this->form_fields;
    }

    public function redirect_url()
    {
        return $this->redirect_url;
    }

    protected function process_form_fields($dom)
    {
        $redirect_parameters = $this->redirect_parameters($dom);

        if (empty($redirect_parameters)) return;

        foreach ($redirect_parameters as $param) {
            $key = (string)$param['name'];
            $value = (string)$param['value'];

            $this->form_fields[$key] = $value;
        }
    }

    protected function redirect_parameters($dom)
    {
        return $dom->xpath(static::REDIRECT_PARAMETER_XPATH);
    }
}
