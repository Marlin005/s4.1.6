<?php

namespace Andchir\CommentsBundle\Service;

use Andchir\CommentsBundle\Document\CommentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

class CommentsManager {

    /** @var ObjectManager */
    protected $dm;
    /** @var array */
    protected $config;

    public function __construct(ContainerInterface $container, array $config = [])
    {
        if (empty($config) && $container->hasParameter('comments_config')) {
            $this->config = $container->getParameter('comments_config');
        } else {
            $this->config = $config;
        }
        if ($container->has('doctrine_mongodb.odm.default_document_manager')) {
            $this->dm = $container->get('doctrine_mongodb.odm.default_document_manager');
        } else {
            $this->dm = $container->get('doctrine.orm.default_entity_manager');
        }
    }

    /**
     * @param int $threadId
     * @return mixed
     */
    public function createComment($threadId = '')
    {
        $className = $this->config['comment_class'] ?? '';
        if (!$className) {
            return null;
        }
        $class = new $className;
        $class->setThreadId($threadId);
        return $class;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    public function getOptionValue($key)
    {
        return $this->config[$key] ?? '';
    }

    /**
     * @return mixed|string
     */
    public function getCommentsClassName()
    {
        return $this->config['comment_class'] ?? '';
    }

    /**
     * @return ObjectRepository
     */
    public function getRepository()
    {
        return $this->dm->getRepository($this->getCommentsClassName());
    }

    /**
     * @return ObjectManager
     */
    public function getEntityManager()
    {
        return $this->dm;
    }

    /**
     * @param $threadId
     * @return float|int
     */
    public function getAverageRating($threadId)
    {
        $comments = $this->getRepository()
            ->findByStatus($threadId, CommentInterface::STATUS_PUBLISHED);
        
        $ratingArr = array_map(function($comment) {
            /** @var CommentInterface $comment */
            return $comment->getVote();
        }, $comments);
        
        if (!count($ratingArr)) {
            return 0;
        }
        return round(array_sum($ratingArr) / count($ratingArr), 2);
    }
    
}
