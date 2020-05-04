<?php

namespace Omnipay\YandexMoney\Tests;

use Omnipay\Omnipay;
use Omnipay\Tests\TestCase;
use Omnipay\YandexMoney\Tests\Gateway as YandexCheckoutGateway;

class OmnipayGatewayIndividualTest extends TestCase
{
    public function testCreate()
    {
        $this->assertEquals(
            Omnipay::create('\\'.YandexCheckoutGateway::class),
            new YandexCheckoutGateway
        );
    }
}
