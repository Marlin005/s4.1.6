<?php

namespace Omnipay\YandexMoney\Tests\Message;

use Omnipay\Tests\TestCase;
use Omnipay\YandexMoney\Tests\Message\IndividualPurchaseResponse;

class IndividualPurchaseResponseTest extends TestCase
{
    public function testConstruct()
    {
        $response = new IndividualPurchaseResponse($this->getMockRequest(),
            array(
                'account' => '410011680044609',
                'form_comment' => 'formComment',
                'orderId' => '1',
                'amount' => '1.0',
                'comment' => 'comment!',
                'method' => 'AC',
                'scid' => '57331',
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


