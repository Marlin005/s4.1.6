<?php

namespace Omnipay\YandexMoney\Tests\Message;

use Omnipay\Tests\TestCase;
use Omnipay\YandexMoney\Tests\Message\CompletePurchaseRequest;

class CompletePurchaseRequestTest extends TestCase
{
    /**
     * @var CompletePurchaseRequest
     */
    protected $request;

    public function setUp()
    {
        $this->request = new CompletePurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
    }

    public function testGetDataSuccess()
    {
        $this->request->initialize(
            array(
                'action' => 'paymentAviso',
                'orderSumAmount' => '1.00',
                'orderSumCurrencyPaycash' => '10643',
                'orderSumBankPaycash' => '1003',
                'orderId' => '25',
                'shopId' => '132',
                'scid' => '57331',
                'invoiceId' => '2000000282924',
                'customerNumber' => '1',
                'password' => 'bytehand',
                'md5' => '2D59782CA25DE1B222ACE1AD5EE7590D'
            )
        );

        $data = $this->request->getData();
        $this->assertSame('paymentAviso', $data['action']);
        $this->assertSame(0, $data['code']);
    }

    public function testGetDataFailure()
    {
        $this->request->initialize(
            array(
                'action' => 'paymentAviso',
                'orderSumAmount' => '1.00',
                'orderSumCurrencyPaycash' => '10643',
                'orderSumBankPaycash' => '1003',
                'orderId' => '25',
                'shopId' => '132',
                'scid' => '57331',
                'invoiceId' => '2000000282924',
                'customerNumber' => '1',
                'password' => 'badPassword',
                'md5' => '2D59782CA25DE1B222ACE1AD5EE7590D'
            )
        );

        $data = $this->request->getData();
        $this->assertSame('paymentAviso', $data['action']);
        $this->assertSame(1, $data['code']);
    }
}
