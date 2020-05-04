<?php

namespace Andchir\CommentsBundle\Repository;

interface CommentRepositoryInterface {

    /**
     * @param string $threadId
     * @param string $status
     * @return array
     */
    public function findByStatus($threadId, $status);

    /**
     * @param string $threadId
     * @return array
     */
    public function findAllByThread($threadId);
}
