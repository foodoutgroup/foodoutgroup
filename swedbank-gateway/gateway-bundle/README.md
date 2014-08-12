# Gateway #

Gateway is a newest way to integrate payments in baltic countries Lithuania, Latvia and Estonia using Symfony 2.


## Configuration ##

#### 1. setup config ####
Add configuration options to `app/config/config.yml`:
```
# Payment gateway
pirminis_gateway:
    swedbank:
        vtid: 12345678
        password: "your_password_here"
```

#### 2. update AppKernel ####
```
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Pirminis\GatewayBundle\PirminisGatewayBundle(),
        );

        return $bundles;
    }
}
```

## How to use ##

Currently there are 2 use cases:

### A. classic banklink ###

#### 1. redirect user to the bank ####

Your controller action could look like this:

```
public function indexAction()
{
    $service = $this->get('pirminis_gateway');

    // set options
    $service->set_options(['order_id' => uniqid(),
                           'price' => 1,
                           'email' => 'b@b.b',
                           'transaction_datetime' => date('Y-m-d H:i:s'),
                           'comment' => 'this is comment',
                           'success_url' => 'http://example.com/success',
                           'failure_url' => 'http://example.com/failure',
                           'language' => 'lt']);

    // get Symfony 2 form
    $form = $service->form_for('swedbank');

    // render template
    $view = 'SwedbankBundle:Default:index.html.twig';
    $params = ['form' => $form->createView()];

    return $this->render($view, $params);
}
```

Your template could look like this:

```
{{ form(form) }}
```

#### 2. query bank for information about payment ####

```
public function successAction(Request $request)
{
    $service = $this->get('pirminis_gateway');

    $service->order_id('swedbank', $request);
    $service->is_authorized('swedbank', $request);
    $service->is_redirect('swedbank', $request);
    $service->requires_investigation('swedbank', $request);
    $service->is_error('swedbank', $request);
    $service->is_cancelled('swedbank', $request);
    $service->communication_error('swedbank', $request);
}
```

### B. credit card banklink ###

#### 1. redirect user to the bank ####

public function indexAction()
{
    $service = $this->get('pirminis_credit_card_gateway');

    // set options
    $service->set_options(['order_id' => uniqid(),
                           'price' => 1,
                           'transaction_datetime' => date('Y-m-d H:i:s'),
                           'comment' => 'TEST',
                           'return_url' => 'http://localhost:3000/return',
                           'expiry_url' => 'http://localhost:3000/expiry']);

    // redirect user to $service->redirect_url('swedbank')
