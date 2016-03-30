<?php

/**
 * Encapsulates a "hosted field" which is just a div that is referenced in the js setup and
 * injected with an iframe from the remote domain.
 *
 * @author  Mark Guinn <mark@adaircreative.com>
 * @date    02.19.2016
 * @package silvershop-braintree
 */
class BraintreeHostedField extends TextField
{
    protected $brainTreeId;

    /**
     * @param string      $name
     * @param null|string $title
     * @param string      $value
     * @param int|null    $maxLength
     * @param Form|null   $form
     */
    public function __construct($name, $title = null, $value = '', $maxLength = null, $form = null)
    {
        parent::__construct($name, $title, $value, $maxLength, $form);
        $this->brainTreeId = $name;
    }

    /**
     * @param array $properties
     *
     * @return string
     */
    public function Field($properties = array())
    {
        return '<div id="bthf-' . $this->getBrainTreeId() . '"></div>';
    }

    /**
     * This is the css selector Braintree's js will use to inject the field
     * @return string
     */
    public function getBraintreeSelector()
    {
        return '#bthf-' . $this->getBrainTreeId();
    }

    /**
     * @return string
     */
    public function getBrainTreeId()
    {
        return $this->brainTreeId;
    }

    /**
     * @param string $brainTreeId
     *
     * @return $this
     */
    public function setBrainTreeId($brainTreeId)
    {
        $this->brainTreeId = $brainTreeId;
        return $this;
    }
}
