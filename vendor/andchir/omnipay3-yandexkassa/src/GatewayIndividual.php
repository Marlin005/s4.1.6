<?php

namespace Omnipay\YandexMoney;

use Omnipay\Common\AbstractGateway;


/**
 * YandexMoney Gateway Individual Class
 * Methods no supported: section for IDE
 * @see GatewayInterface
 *
 * @method completeAuthorize(array $options = array()) - not support
 * @method capture(array $options = array())           - not support
 * @method refund(array $options = array())            - not support
 * @method void(array $options = array())              - not support
 * @method createCard(array $options = array())        - not support
 * @method updateCard(array $options = array())        - not support
 * @method deleteCard(array $options = array())        - not support
 * @method completePurchase(array $options = array())  - not support

 */
class GatewayIndividual extends AbstractGateway
{
    public function getName()
    {
        return 'YandexMoneyIndividual';
    }

    public function getDefaultParameters()
    {
        return array(
            'account' => '',
            'password'  => '',
            'method' => '',
            'orderId' => '',
            'currencyNum' => '',
            'orderNumber' => '',
            'label' => '',
            'operation_id' => '',
            'datetime' => '',
            'sender' => '',
            'codepro' => '',
            'notification_type' => '',
            'customerNumber' => '',
            'sha1_hash' => '',
            'returnUrl' => '',
            'cancelUrl' => '',
        );
    }

 
    public function getAccount()
    {
        return $this->getParameter('account');
    }

    public function setAccount($value)
    {
        return $this->setParameter('account', $value);
    }

    public function setMethod($value)
    {
        return $this->setParameter('method', $value);
    }
    public function getMethod()
    {
        return $this->getParameter('method');
    }
    public function setOrderId($value)
    {
        return $this->setParameter('orderId', $value);
    }
    public function getOrderId()
    {
        return $this->getParameter('orderId');
    }
    public function setOrderNumber($value)
    {
        return $this->setParameter('orderNumber', $value);
    }
    public function getOrderNumber()
    {
        return $this->getParameter('orderNumber');
    }
    public function setCustomerNumber($value)
    {
        return $this->setParameter('customerNumber', $value);
    }
     public function getCustomerNumber()
    {
        return $this->getParameter('customerNumber');
    }

    public function setLabel($value)
    {
        return $this->setParameter('label', $value);
    }
    public function getLabel()
    {
        return $this->getParameter('label');
    }
    public function getPassword()
    {
        return $this->getParameter('password');
    }
    public function getNotificationType()
    {
         return $this->getParameter('notification_type');
    }
    public function getNotification_type()
    {
         return $this->getParameter('notification_type');
    }
    public function setNotificationType($value)
    {
         return $this->setParameter('notification_type', $value);
    }
    public function setNotification_type($value)
    {
         return $this->setParameter('notification_type', $value);
    }
    public function setPassword($value)
    {
        return $this->setParameter('password', $value);
    }
    public function getOperation_id()
    {
         return $this->getParameter('operation_id');
    }
    public function setOperation_id($value)
    {
        return $this->setParameter('operation_id', $value);
    }
    public function getOperationId()
    {
         return $this->getParameter('operation_id');
    }
    public function setOperationId($value)
    {
        return $this->setParameter('operation_id', $value);
    }
    public function getCurrencyNum()
    {
        return $this->getParameter('currencyNum');
    }

    public function setCurrencyNum($value)
    {
        return $this->setParameter('currencyNum', $value);
    }
    public function getDatetime()
    {
         return $this->getParameter('datetime');
    }
    public function setDatetime($value)
    {
         return $this->setParameter('datetime', $value);
    }
    public function getSender()
    {
         return $this->getParameter('sender');
    }
    public function setSender($value)
    {
         return $this->setParameter('sender', $value);
    }
    public function getCodepro()
    {
         return $this->getParameter('codepro');
    }

    public function setCodepro($value)
    {
         return $this->setParameter('codepro', $value);
    }

    public function setSha1Hash($value)
    {
        return $this->setParameter('sha1_hash', $value);
    }

    public function getSha1Hash()
    {
        return $this->getParameter('sha1_hash');
    }

    public function setSha1_hash($value)
    {
        return $this->setParameter('sha1_hash', $value);
    }

    public function getSha1_hash()
    {
        return $this->getParameter('sha1_hash');
    }

    public function setReturnUrl($value)
    {
        return $this->setParameter('returnUrl', $value);
    }

    public function getReturnUrl()
    {
        return $this->getParameter('returnUrl');
    }

    public function setCancelUrl($value)
    {
        return $this->setParameter('cancelUrl', $value);
    }

    public function getCancelUrl()
    {
        return $this->getParameter('cancelUrl');
    }

    public function authorize(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\YandexMoney\Message\IndividualAuthorizeRequest', $parameters);
    }

    public function purchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\YandexMoney\Message\IndividualPurchaseRequest', $parameters);
    }

}
