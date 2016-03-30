<?php

/**
 * @author     Mark Guinn <mark@adaircreative.com>
 * @date       03.29.2016
 * @package    silvershop-braintree
 * @subpackage tests
 */
class BraintreePaymentCheckoutComponentTest extends SapphireTest
{
    protected $usesDatabase = false;

    /** @var Order */
    protected $cart;

    /** @var BraintreePaymentCheckoutComponent */
    protected $sut;

    public function setUp()
    {
        parent::setUp();
        $mockRequest2 = $this->getMockBuilder('Omnipay\Braintree\Message\ClientTokenResponse')
            ->disableOriginalConstructor()->getMock();
        $mockRequest2->method('getToken')->will($this->returnValue('abc123'));
        $mockRequest1 = $this->getMockBuilder('Omnipay\Braintree\Message\ClientTokenRequest')
            ->disableOriginalConstructor()->getMock();
        $mockRequest1->method('send')->will($this->returnValue($mockRequest2));
        $mockGateway = $this->getMock('Omnipay\Braintree\Gateway');
        $mockGateway->method('clientToken')->will($this->returnValue($mockRequest1));
        $this->cart = new Order; //$this->getFixtureFactory()->createObject('Order', 'o', ['Status' => 'Cart']);
        ShoppingCart::singleton()->setCurrent($this->cart);
        $this->sut = new BraintreePaymentCheckoutComponent();
        /** @var \Omnipay\Braintree\Gateway $mockGateway */
        $this->sut->setGateway($mockGateway);
    }

    public function testInjectCallbacks()
    {
        $this->sut->setJsCallbacks(
            [
                'onPaymentMethodReceived' => 'function(e){console.log("yes");}',
                'onError'                 => 'function(e){console.log("no");}',
            ]
        );
        $this->sut->getFormFields($this->cart);
        $js = Requirements::backend()->get_custom_scripts();
        $this->assertContains('onPaymentMethodReceived', $js);
        $this->assertContains('function(e){console.log("yes");}', $js);
        $this->assertContains('onError', $js);
        $this->assertContains('function(e){console.log("no");}', $js);
    }

    public function testHasNonceField()
    {
        $fields = $this->sut->getFormFields($this->cart);
        $this->assertInstanceOf('HiddenField', $fields->fieldByName('payment_method_nonce'));
    }

    public function testRequestsClientToken()
    {
        $this->sut->getFormFields($this->cart);
        $js = Requirements::backend()->get_custom_scripts();
        $this->assertContains('abc123', $js);
    }
}
