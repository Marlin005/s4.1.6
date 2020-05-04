<?php

namespace Omnipay\YandexMoney\Tests\Message;

use Omnipay\Tests\TestCase;
use Omnipay\YandexMoney\Tests\Message\AuthorizeRequest;

class AuthorizeRequestTest extends TestCase
{
    /**
     * @var AuthorizeRequest
     */
    protected $request;

    public function setUp()
    {
        $this->request = new AuthorizeRequest($this->getHttpClient(), $this->getHttpRequest());

    }

    public function testGetDataSuccess()
    {
        $this->request->initialize(
            array(
                'action' => 'checkOrder',
                'orderSumAmount' => '1.00',
                'orderSumCurrencyPaycash' => '10643',
                'orderSumBankPaycash' => '1003',
                'orderId' => '25',
                'shopId' => '132',
                'scid' => '57331',
                'invoiceId' => '2000000282924',
                'customerNumber' => '1',
                'password' => 'bytehand',
                'md5' => '90BE7B50AB81D7783846D0E2D2A095C6',
            )
        );

        $data = $this->request->getData();
        //    var_dump( $data['code'], $this->request->getOrderSumAmount(),$this->request-> getAmount());die;
        $this->assertSame('checkOrder', $data['action']);
        $this->assertSame(0, $data['code']);
    }

    public function testGetDataFailure()
    {
        $this->request->initialize(
            array(
                'action' => 'checkOrder',
                'orderSumAmount' => '1.00',
                'orderSumCurrencyPaycash' => '10643',
                'orderSumBankPaycash' => '1003',
                'orderId' => '25',
                'shopId' => '132',
                'scid' => '57331',
                'invoiceId' => '2000000282924',
                'customerNumber' => '1',
                'password' => 'badPassword',
                'md5' => '90BE7B50AB81D7783846D0E2D2A095C6'
            )
        );

        $data = $this->request->getData();
        $this->assertSame('checkOrder', $data['action']);
        $this->assertSame(1, $data['code']);
    }
}
