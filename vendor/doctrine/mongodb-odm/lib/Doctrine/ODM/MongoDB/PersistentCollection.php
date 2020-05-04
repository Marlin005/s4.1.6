<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB;

use Doctrine\Common\Collections\Collection as BaseCollection;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionTrait;

/**
 * A PersistentCollection represents a collection of elements that have persistent state.
 */
final class PersistentCollection implements PersistentCollectionInterface
{
    use PersistentCollectionTrait;

    /**
     * @param BaseCollection $coll
     */
    public function __construct(BaseCollection $coll, DocumentManager $dm, UnitOfWork $uow)
    {
        $this->coll = $coll;
        $this->dm   = $dm;
        $this->uow  = $uow;
    }
}
