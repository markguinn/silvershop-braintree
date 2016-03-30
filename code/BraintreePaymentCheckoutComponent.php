<?php

/**
 * This replaces the default OnsitePaymentCheckoutComponent and uses braintree's dropin UI,
 * returning only a nonce to the server.
 *
 * NOTE: This handles ALL javascript setup so you don't need to do anything. By default it
 * will replace OnsitePaymentCheckoutComponent via the injector in either single page or
 * multi-step checkout.
 *
 * @author  Mark Guinn <mark@adaircreative.com>
 * @date    02.19.2016
 * @package silvershop-braintree
 */
class BraintreePaymentCheckoutComponent extends OnsitePaymentCheckoutComponent
{
    /** @var array  */
    private static $callbacks = [
        'onPaymentMethodReceived' => '
            function(e) {
                if (e.nonce) {
                    jQuery("input[data-braintree=nonce]").val(e.nonce)
                        .closest("form").submit()
                        .find(".action").prop("disabled", true);
                }
            }
        '
        //'onError' => 'function(error){ alert(error.message); }';
    ];

    /** @var array - allows you to override the config */
    protected $jsCallbacks;

    /** @var bool - if for some reason the gateway is not actually braintree, fall back to OnsitePayment */
    protected $isBraintree;

    /** @var \Omnipay\Common\AbstractGateway|\Omnipay\Braintree\Gateway */
    protected $gateway;

    /**
     * @param Order $order
     *
     * @return \Omnipay\Common\AbstractGateway|\Omnipay\Braintree\Gateway
     */
    protected function getGateway($order)
    {
        if (!isset($this->gateway)) {
            $tempPayment = new Payment(
                [
                    'Gateway' => Checkout::get($order)->getSelectedPaymentMethod(false),
                ]
            );
            $service = PurchaseService::create($tempPayment);
            $this->gateway = $service->oGateway();
            $this->isBraintree = ($this->gateway instanceof \Omnipay\Braintree\Gateway);
        }

        return $this->gateway;
    }

    /**
     * @param \Omnipay\Common\AbstractGateway|\Omnipay\Braintree\Gateway $gateway
     * @return $this
     */
    public function setGateway($gateway)
    {
        $this->gateway = $gateway;
        $this->isBraintree = ($this->gateway instanceof \Omnipay\Braintree\Gateway);
        return $this;
    }

    /**
     * Get form fields for manipulating the current order,
     * according to the responsibility of this component.
     *
     * @param Order $order
     * @param Form  $form
     *
     * @return FieldList
     */
    public function getFormFields(Order $order, Form $form = null)
    {
        $gateway = $this->getGateway($order);
        if (!$this->isBraintree) {
            return parent::getFormFields($order);
        }

        // Generate the token for the javascript to use
        $clientToken = $gateway->clientToken()->send()->getToken();

        // Generate the standard set of fields and allow it to be customised
        $fields = FieldList::create(
            [
                LiteralField::create('BraintreePlaceholder', '<div id="braintree-ui"></div>'),
                HiddenField::create('payment_method_nonce', '', '')->setAttribute('data-braintree', 'nonce'),
            ]
        );

        $this->extend('updateFormFields', $fields);

        // Generate a basic config and allow it to be customised
        $config = [
            'id'        => $form ? $form->getHTMLID() : 'PaymentForm_OrderForm',
            'container' => 'braintree-ui',
        ];
        $this->extend('updateBraintreeConfig', $config);

        $rawConfig = json_encode($config);
        $rawConfig = $this->injectCallbacks($rawConfig);
        $this->extend('updateRawBraintreeConfig', $rawConfig);

        // Finally, add the javascript to the page
        Requirements::javascript('https://js.braintreegateway.com/js/braintree-2.20.0.min.js');
        Requirements::customScript("braintree.setup('{$clientToken}', 'dropin', $rawConfig);", 'BraintreeJS');

        return $fields;
    }

    /**
     * Takes the basic json config and manually adds any callbacks requested.
     *
     * @param string $rawConfig
     * @return string
     */
    protected function injectCallbacks($rawConfig)
    {
        $rawConfig = substr($rawConfig, 0, -1);

        foreach ($this->getJsCallbacks() as $key => $func) {
            $rawConfig .= ', "' . $key . '":' . $func;
        }

        return $rawConfig . '}';
    }

    /**
     * Get the data fields that are required for the component.
     *
     * @param  Order $order [description]
     *
     * @return array        required data fields
     */
    public function getRequiredFields(Order $order)
    {
        $this->getGateway($order);
        if (!$this->isBraintree) {
            return parent::getRequiredFields($order);
        } else {
            return [];
        }
    }

    /**
     * Is this data valid for saving into an order?
     *
     * This function should never rely on form.
     *
     * @param Order $order
     * @param array $data data to be validated
     *
     * @throws ValidationException
     * @return boolean the data is valid
     */
    public function validateData(Order $order, array $data)
    {
        $this->getGateway($order);
        if (!$this->isBraintree) {
            return parent::validateData($order, $data);
        } else {
            // NOTE: Braintree will validate clientside and if for some reason that falls through
            // it will fail on payment and give an error then. It would be a lot of work to get
            // the nonce to be namespaced so it could be passed here and there would be no point.
            return true;
        }
    }

    /**
     * Get required data out of the model.
     *
     * @param  Order $order order to get data from.
     *
     * @return array        get data from model(s)
     */
    public function getData(Order $order)
    {
        $this->getGateway($order);
        if (!$this->isBraintree) {
            return parent::getData($order);
        } else {
            return [];
        }
    }

    /**
     * Set the model data for this component.
     *
     * This function should never rely on form.
     *
     * @param Order $order
     * @param array $data data to be saved into order object
     *
     * @throws Exception
     * @return Order the updated order
     */
    public function setData(Order $order, array $data)
    {
        $this->getGateway($order);
        if (!$this->isBraintree) {
            return parent::setData($order, $data);
        } else {
            return [];
        }
    }

    /**
     * This controls the field styling. It's basically CSS converted to php arrays or yml.
     *
     * @see https://developers.braintreepayments.com/guides/hosted-fields/styling/javascript/v2
     * @return array
     */
    public function getJsCallbacks()
    {
        if (!isset($this->jsCallbacks)) {
            $this->jsCallbacks = $this->config()->callbacks;
        }

        if (!is_array($this->jsCallbacks)) {
            $this->jsCallbacks = [];
        }

        return $this->jsCallbacks;
    }

    /**
     * This completely overrides anything set in the config. It does not merge the keys at all.
     *
     * @param array $callbacks
     *
     * @return $this
     */
    public function setJsCallbacks($callbacks)
    {
        $this->jsCallbacks = $callbacks;
        return $this;
    }
}
