<?php

namespace Andchir\OmnipayBundle\Document;

interface PaymentInterface {

    const STATUS_CREATED = 'created';
    const STATUS_CANCELED = 'canceled';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ERROR = 'error';

    public function getId();

    public function setEmail($email);

    public function getEmail();

    public function setStatus($status);

    public function getStatus();

    public function setUserId($userId);

    public function getUserId();

    public function setAmount($amount);

    public function getAmount();

    public function setCurrency($currency);

    public function getCurrency();

    public function setOptions($options);

    public function getOptions();

    public function setCreatedDate($createdDate);

    public function getCreatedDate();

    public function setUpdatedDate($updatedDate);

    public function getUpdatedDate();

    public function setOrderId($orderId);

    public function getOrderId();

    public function setDescription($description);

    public function getDescription();

}

