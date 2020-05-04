<?php

namespace Omnipay\YandexMoney\Tests\Message;

use Omnipay\Tests\TestCase;
use Omnipay\YandexMoney\Tests\Message\IndividualAuthorizeRequest;

class IndividualAuthorizeRequestTest extends TestCase
{
    /**
     * @var IndividualAuthorizeRequest
     */
    protected $request;

    public function setUp()
    {
        $this->request = new IndividualAuthorizeRequest($this->getHttpClient(), $this->getHttpRequest());
    }

    public function testGetDataSuccess()
    {
        $this->request->initialize(
            array(
                "operation_id" => "937356121364090009",
                "sha1_hash" => "3ec98f09655ce22d014f1a62706057c178fc0e66",
                "notification_type" => "p2p-incoming",
                "label" => "18",
                "currency" => "643",
                "amount" => "0.99",
                "sender" => "41001451017477",
                "codepro" => "false",
                "datetime" => "2014-11-07T12:21:00Z",
                "password" => "dUtlwyajCX6osFzTuZriXPQJ"
            )
        );

        $data = $this->request->getData();
        $this->assertSame('18', $data['orderNumber']);
        $this->assertSame(0, $data['code']);
    }

    public function testGetDataFailure()
    {
        $this->request->initialize(
            array(
                "operation_id" => "937356121364090009",
                "sha1_hash" => "3ec98f09655ce22d014f1a62706057c178fc0e66",
                "notification_type" => "p2p-incoming",
                "label" => "18",
                "currency" => "643",
                "amount" => "0.99",
                "sender" => "41001451017477",
                "codepro" => "false",
                "datetime" => "2014-11-07T12:21:00Z",
                "password" => "badPass"
            )
        );

        $data = $this->request->getData();
        $this->assertSame('18', $data['orderNumber']);
        $this->assertSame(1, $data['code']);
    }
}
