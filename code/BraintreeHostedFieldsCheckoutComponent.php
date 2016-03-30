<?php

/**
 * This replaces the default OnsitePaymentCheckoutComponent and uses braintree's javascript
 * and hosted fields, returning only a nonce to the server.
 *
 * This requires more thought and work to get going but allows more flexibility and styling and behaviour.
 * @see https://developers.braintreepayments.com/reference/client-reference/javascript/v2/hosted-fields
 *
 * @author  Mark Guinn <mark@adaircreative.com>
 * @date    02.26.2016
 * @package silvershop-braintree
 */
class BraintreeHostedFieldsCheckoutComponent extends BraintreePaymentCheckoutComponent
{
    /** @var bool - should we add placeholders instead of labels to the fields? */
    private static $use_placeholders = true;

    /** @var array - set the styles for the fields via yml */
    private static $field_styles = [];

    /** @var array - override the yml for styles if desired */
    protected $fieldStyles;

    /**
     * This controls the field styling. It's basically CSS converted to php arrays or yml.
     *
     * @see https://developers.braintreepayments.com/guides/hosted-fields/styling/javascript/v2
     * @return array
     */
    public function getFieldStyles()
    {
        if (!isset($this->fieldStyles)) {
            $this->fieldStyles = $this->config()->field_styles;
        }

        return $this->fieldStyles;
    }

    /**
     * @param array $fieldStyles
     *
     * @return $this
     */
    public function setFieldStyles($fieldStyles)
    {
        $this->fieldStyles = $fieldStyles;
        return $this;
    }

    /**
     * Get form fields for manipulating the current order,
     * according to the responsibilty of this component.
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
                BraintreeHostedField::create(
                    'number',
                    _t('BraintreePaymentCheckoutComponent.CardNumber', 'Debit/Credit Card Number')
                ),
                BraintreeHostedField::create(
                    'cvv',
                    _t('BraintreePaymentCheckoutComponent.CVV', 'Security Code')
                ),
                BraintreeHostedField::create(
                    'expirationDate',
                    _t('BraintreePaymentCheckoutComponent.ExpirationDate', 'Expiration Date (MM/YYYY)')
                ),
            ]
        );

        if ($this->config()->use_placeholders) {
            foreach ($fields as $field) {
                if ($field->Title()) {
                    $field->setAttribute('placeholder', $field->Title())->setTitle(null);
                }
            }
        }

        $this->extend('updateFormFields', $fields);

        // Generate a basic config and allow it to be customised
        $config = [
            'id'           => $form ? $form->getHTMLID() : 'PaymentForm_OrderForm',
            'hostedFields' => $this->getFieldConfig($fields),
        ];
        $this->extend('updateBraintreeConfig', $config);

        $rawConfig = json_encode($config);
        $rawConfig = $this->injectCallbacks($rawConfig);
        $this->extend('updateRawBraintreeConfig', $rawConfig);

        // Finally, add the javascript to the page
        Requirements::javascript('https://js.braintreegateway.com/js/braintree-2.20.0.min.js');
        Requirements::customScript("braintree.setup('{$clientToken}', 'custom', $rawConfig);", 'BrainTreeJS');

        return $fields;
    }

    /**
     * Converts the braintree fields in this fieldlist into configuration format
     *
     * @param FieldList $fields
     *
     * @return string
     */
    private function getFieldConfig($fields)
    {
        $cfg = [
            'styles' => $this->getFieldStyles(),
        ];

        foreach ($fields as $field) {
            if ($field instanceof BraintreeHostedField) {
                $cfg[$field->getName()] = [
                    'selector'    => $field->getBraintreeSelector(),
                    'placeholder' => $field->getAttribute('placeholder'),
                ];
            }
        }

        return $cfg;
    }
}
