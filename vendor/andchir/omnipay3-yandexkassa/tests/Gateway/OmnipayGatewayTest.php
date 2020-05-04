<?php

namespace Omnipay\YandexMoney\Tests;

use Omnipay\Omnipay;
use Omnipay\Tests\TestCase;
use Omnipay\YandexMoney\Tests\GatewayIndividual as YandexMoneyGateway;

class OmnipayGatewayTest extends TestCase
{
    public function testCreate()
    {
        $this->assertEquals(
            Omnipay::create('\\'.YandexMoneyGateway::class),
            new YandexMoneyGateway
        );
    }
}
