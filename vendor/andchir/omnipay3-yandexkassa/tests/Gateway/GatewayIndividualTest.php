<?php

namespace Omnipay\YandexMoney\Tests;

use Omnipay\Tests\GatewayTestCase;
use Omnipay\YandexMoney\Tests\GatewayIndividual as YandexMoneyGateway;


class GatewayIndividualTest extends GatewayTestCase
{
    protected $authorizeOptions = array(
        'notification_type' => 'p2p-incoming',
        'operation_id' => '937356121364090009',
        'amount' => '0.99',
        'currency' => '643',
        'datetime' => '2014-11-07T12:21:00Z',
        'sender' => '41001451017477',
        'codepro' => 'false',
        'label' => '18',
        'sha1_hash' => '3ec98f09655ce22d014f1a62706057c178fc0e66'
    );
    
    protected $purchaseOptions = array(
        'form_comment' => 'formcomment',
        'orderId' => '123',
        'amount' => '10.00',
        'method' => 'AC',
        'comment' => 'comment',
        'returnUrl' => 'http://example.com/success',
        'cancelUrl' => 'http://example.com/cancel'
    );

    public function setUp()
    {
        parent::setUp();
        if (!$this->gateway) {
            $this->gateway = new YandexMoneyGateway(
                $this->getHttpClient(),
                $this->getHttpRequest()
            );
        }
        
        // 0_o
        $this->gateway->setAccount('410011680044609');
        $this->gateway->setPassword('dUtlwyajCX6osFzTuZriXPQJ');
    }


    public function testAuthorize()
    {
        $response = $this->gateway->authorize($this->authorizeOptions)->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('18', $response->getTransactionReference());
        $this->assertFalse($response->getMessage());
    }


    public function testPurchase()
    {
        $response = $this->gateway->purchase($this->purchaseOptions)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $this->assertNull($response->getTransactionReference());
        $this->assertNull($response->getMessage());
        $this->assertContains('money.yandex.ru', $response->getRedirectUrl());
        $this->assertSame('POST', $response->getRedirectMethod());
        $this->assertNotNull($response->getRedirectData());
    }


}
