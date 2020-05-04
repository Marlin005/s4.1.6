<?php

namespace Andchir\CommentsBundle\Controller\Admin;

use Andchir\CommentsBundle\Document\CommentInterface;
use Andchir\CommentsBundle\Repository\CommentRepositoryAbstract;
use Andchir\CommentsBundle\Service\CommentsManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

if (class_exists('\App\Controller\Admin\StorageControllerAbstract')) {

    /**
     * Class CommentsController
     * @Route("/admin/comments")
     */
    class CommentsController extends \App\Controller\Admin\StorageControllerAbstract {

        /** @var CommentsManager */
        protected $commentsManager;

        public function __construct(CommentsManager $commentsManager)
        {
            $this->commentsManager = $commentsManager;
        }
        
        /**
         * @param $data
         * @param int $itemId
         * @return array
         */
        public function validateData($data, $itemId = null)
        {
            if (empty($data)) {
                return ['success' => false, 'msg' => 'Data is empty.'];
            }
            return ['success' => true];
        }

        /**
         * @param $data
         * @param null $itemId
         * @return JsonResponse
         * @throws \Doctrine\ODM\MongoDB\MongoDBException
         */
        public function createUpdate($data, $itemId = null)
        {
            /** @var TranslatorInterface $translator */
            $translator = $this->get('translator');
            /** @var \Doctrine\ODM\MongoDB\DocumentManager $dm */
            $dm = $this->get('doctrine_mongodb')->getManager();

            /** @var CommentInterface $item */
            if($itemId){
                /** @var CommentInterface $item */
                $item = $this->getRepository()->find($itemId);
                if(!$item){
                    return $this->setError($translator->trans('Item not found.', [], 'validators'));
                }
            } else {
                $item = $this->commentsManager->createComment();
                $item->setAuthor($this->getUser());
            }

            $item
                ->setStatus($data['status'])
                ->setThreadId($data['threadId'])
                ->setVote($data['vote'])
                ->setComment($data['comment'])
                ->setReply($data['reply']);

            if (!$item->getId()) {
                $dm->persist($item);
            }
            $dm->flush();

            // Dispatch event before create
            /** @var EventDispatcherInterface $eventDispatcher */
            $eventDispatcher = $this->get('event_dispatcher');
            $event = new GenericEvent($item);
            $eventDispatcher->dispatch($event, CommentInterface::COMMENT_STATUS_UPDATED)->getSubject();

            return $this->json($item, 200, [], ['groups' => ['details']]);
        }

        /**
         * @return CommentRepositoryAbstract
         */
        public function getRepository()
        {
            return $this->get('doctrine_mongodb')
                ->getManager()
                ->getRepository('App\MainBundle\Document\Comment');
        }
    }

} else {
    class CommentsController extends AbstractController { }
}
