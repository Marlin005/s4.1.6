<?php

namespace Omnipay\YandexMoney\Tests\Message;

use Omnipay\Tests\TestCase;
use Omnipay\YandexMoney\Tests\Message\PurchaseResponse;

class PurchaseResponseTest extends TestCase
{
    public function testConstruct()
    {
        $response = new PurchaseResponse($this->getMockRequest(),
            array(
                'method' => 'AC',
                'amount' => '1.00',
                'currencyNum' => '10643',
                'orderId' => '25',
                'shopId' => '132',
                'scid' => '57331',
                'customerNumber' => '1',
                'returnUrl' => 'http://example.com/return',
                'cancelUrl' => 'http://example.com/cancel'
            )
        );

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $this->assertSame('POST', $response->getRedirectMethod());
        $this->assertNotNull($response->getRedirectData());
    }
}


