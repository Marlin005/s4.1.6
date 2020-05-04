<?php

namespace Omnipay\YandexMoney\Tests\Message;

use Omnipay\Tests\TestCase;
use Omnipay\YandexMoney\Tests\Message\PurchaseRequest;

class PurchaseRequestTest extends TestCase
{
    /**
     * @var PurchaseRequest
     */
    protected $request;

    public function setUp()
    {
        $this->request = new PurchaseRequest($this->getHttpClient(), $this->getHttpRequest());
    }

    public function testGetData()
    {
        $this->request->initialize(
            array(
                'method' => 'AC',
                'amount' => '1.00',
                'currencyNum' => '10643',
                'orderId' => '25',
                'shopId' => '132',
                'scid' => '57331',
                'customerNumber' => '1',
                'password' => 'bytehand',
                'returnUrl' => 'http://example.com/return',
                'cancelUrl' => 'http://example.com/cancel'
            )
        );

        $data = $this->request->getData();
        $this->assertSame('AC', $data['paymentType']);
        $this->assertSame('10643', $data['orderSumCurrencyPaycash']);
    }
}
