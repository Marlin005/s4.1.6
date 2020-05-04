<?php

namespace Andchir\CommentsBundle\Document;

interface CommentInterface {

    const STATUS_PUBLISHED = 'published';
    const STATUS_DELETED = 'deleted';
    const STATUS_PENDING = 'pending';

    const COMMENT_BEFORE_CREATE = 'comment.before_create';
    const COMMENT_STATUS_UPDATED = 'comment.status_update';

    public function getThreadId();

    public function setThreadId($threadId);

    public function getComment();

    public function setComment($comment);

    public function getVote();

    public function setVote($vote);

    public function getStatus();

    public function setStatus($status);

    public function getAuthor();

    public function setAuthor($author);

    public function getCreatedTime();

    public function setCreatedTime($createdTime);

    public function getPublishedTime();

    public function setPublishedTime($publishedTime);

    public function getIsActive();

    public function prePersist();

}
