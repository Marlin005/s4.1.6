<?php

namespace Omnipay\YandexMoney\Tests\Message;

use Omnipay\Tests\TestCase;
use Omnipay\YandexMoney\Tests\Message\CompletePurchaseResponse;

class CompletePurchaseResponseTest extends TestCase
{
    /**
     * @var CompletePurchaseResponse
     */
    protected $request;

    public function testSuccess()
    {
        $response = new CompletePurchaseResponse($this->getMockRequest(),
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
        $this->assertContains('paymentAvisoResponse', $response->getMessage());
        $this->assertSame('5', $response->getTransactionReference());
    }

    public function testFailure()
    {
        $response = new CompletePurchaseResponse($this->getMockRequest(),
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
        $this->assertContains('paymentAvisoResponse', $response->getMessage());
        $this->assertSame('5', $response->getTransactionReference());
    }
}


