<?php

namespace Omnipay\YandexMoney\Tests\Message;

use Omnipay\Tests\TestCase;
use Omnipay\YandexMoney\Tests\Message\AuthorizeResponse;

class AuthorizeResponseTest extends TestCase
{
    
    public function testSuccess()
    {
        $response = new AuthorizeResponse($this->getMockRequest(),
            array(
                'code' => 0,
                'invoiceId' => '1',
                'shopId' => '123',
                'orderNumber' => '5'
            )
        );

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertNotNull($response->getMessage());
        $this->assertContains('checkOrderResponse', $response->getMessage());
        $this->assertSame('5', $response->getTransactionReference());
    }

    public function testFailure()
    {
        $response = new AuthorizeResponse($this->getMockRequest(),
            array(
                'code' => 1,
                'invoiceId' => '1',
                'shopId' => '123',
                'orderNumber' => '5'
            )
        );

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertNotNull($response->getMessage());
        $this->assertContains('checkOrderResponse', $response->getMessage());
        $this->assertSame('5', $response->getTransactionReference());
    }
}


