# Gateway #

Gateway is a newest way to integrate payments in baltic countries Lithuania, Latvia and Estonia.

### Installation ###

Use composer.

#### 1. add third party repository: ####
```
"repositories": [
    {
        "type": "git",
        "url": "https://bitbucket.org/pirminis/gateway"
    }
]
```

#### 2. require it: ####
stable:
```
"require": {
    "pirminis/gateway": "~1.0"
}
```

development:
```
"require": {
    "pirminis/gateway": "@dev"
}
```

### How to use ###

Currently there are 2 use cases:

#### 1. redirect user to the bank ####
```
use Pirminis\Gateway\Swedbank\Banklink\Request;
use Pirminis\Gateway\Swedbank\Banklink\Request\Parameters;
use Pirminis\Gateway\Swedbank\Banklink\Sender;
use Pirminis\Gateway\Swedbank\Banklink\Response;
use Pirminis\Gateway\Swedbank\Banklink\Form;

// create request and set mandatory parameters
$params = new Parameters();
$params->set('client', '123')
       ->set('password', 'abc')
       ->set('order_id', '555')
       ->set('price', '1699')
       ->set('email', 'some@email.com')
       ->set('transaction_datetime', '1999-01-01 01:01:01')
       ->set('comment', 'bring it!')
       ->set('success_url', 'http://some.com/success')
       ->set('failure_url', 'http://some.com/failure')
       ->set('language', 'lt');

$request = new Request($params);

// create sender
$sender = new Sender($request->xml());

// send request and create response
$response = new Response($sender->send());

if (!$response->is_redirect()) return null;

// create form
$form = new Form($response->dom(), $response->redirect_url());

// two methods are now accessible
$form->form_fields();
$form->redirect_url();

// use form_fields to automatically fill in form
// use redirect URL as action in POST form
// and finally just submit your form
```

#### 2. query bank for information about payment ####

```
use Pirminis\Gateway\Swedbank\Banklink\TransactionQuery\Request;
use Pirminis\Gateway\Swedbank\Banklink\Request\Parameters;
use Pirminis\Gateway\Swedbank\Banklink\Sender;
use Pirminis\Gateway\Swedbank\Banklink\Response;

// create transaction query request
$request = new Request('client',
                       'password',
                       $_GET['DPGReferenceId']);

// create sender
$sender = new Sender($request->xml());

// send request and create response
$response = new Response($sender->send());

// now several methods are available:

// is payment successful and paid?
$response->is_authorized();

// is user can be redirected to the bank?
$response->is_redirect();

// is payment being processed?
$response->requires_investigation();

// is payment erroneous?
$response->is_error();

// is payment cancelled?
$response->is_cancelled();

// did communication error with the bank just occured?
$response->communication_error();

// get redirect_url for redirect form
$response->redirect_url()

// get order id
$response->order_id()
```
