<?php

namespace Andchir\OmnipayBundle\Repository;

use Doctrine\Common\Persistence\ObjectRepository;

interface OrderRepositoryInterface extends ObjectRepository {

    public function getOneByUser($id, $userId);

    public function getAllByUserId($userId, $skip = 0, $limit = 100);

    public function getCountByUserId($userId);

}
