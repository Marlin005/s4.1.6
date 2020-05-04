<?php

namespace Andchir\CommentsBundle\Controller;

use Andchir\CommentsBundle\Document\CommentInterface;
use Andchir\CommentsBundle\Form\Type\AddCommentType;
use Andchir\CommentsBundle\Service\CommentsManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class DefaultController
 * @package Andchir\CommentsBundle\Controller
 *
 * @Route("/comments")
 */
class DefaultController extends AbstractController
{
    /** @var CommentsManager */
    protected $commentsManager;

    public function __construct(CommentsManager $commentsManager)
    {
        $this->commentsManager = $commentsManager;
    }

    /**
     * @Route("/{threadId}", name="comments_list", methods={"GET"})
     * @param Request $request
     * @param string $threadId
     * @return Response
     */
    public function getThreadAction(Request $request, $threadId)
    {
        /** @var CommentInterface $comment */
        $comment = $this->commentsManager->createComment($threadId);

        $form = $this->createForm(AddCommentType::class, $comment);

        if ($this->isGranted('ROLE_ADMIN')) {
            $comments = $this->commentsManager
                ->getRepository()
                ->findAllByThread($threadId);
        } else {
            $comments = $this->commentsManager
                ->getRepository()
                ->findByStatus($threadId, CommentInterface::STATUS_PUBLISHED);
        }

        $currentUrl = $request->get('currentUrl', '');

        return $this->render('@Comments/Default/comments.html.twig', [
            'form' => $form->createView(),
            'comments' => $comments,
            'currentUrl' => $currentUrl
        ]);
    }

    /**
     * @Route("/add", name="comment_add", methods={"POST"})
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param EventDispatcherInterface $eventDispatcher
     * @return Response
     */
    public function addCommentAction(Request $request, TranslatorInterface $translator, EventDispatcherInterface $eventDispatcher)
    {
        $statusDefault = $this->commentsManager->getOptionValue('status_default');
        $referer = $request->headers->get('referer');
        /** @var CommentInterface $comment */
        $comment = $this->commentsManager->createComment();
        $form = $this->createForm(AddCommentType::class, $comment);

        $form->handleRequest($request);

        if (!$this->isGranted('ROLE_USER')) {
            $form->addError(new FormError($translator->trans('Only authorized users can post reviews.')));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $comment = $form->getData();
            $comment
                ->setAuthor($this->getUser())
                ->setStatus($statusDefault);

            // Dispatch event before create
            $event = new GenericEvent($comment);
            $comment = $eventDispatcher->dispatch($event, CommentInterface::COMMENT_BEFORE_CREATE)->getSubject();
            
            $this->commentsManager->getEntityManager()->persist($comment);
            $this->commentsManager->getEntityManager()->flush();
            
            if ($comment->getIsActive()) {
                $eventDispatcher->dispatch($event, CommentInterface::COMMENT_STATUS_UPDATED)->getSubject();
            }

            if ($statusDefault == CommentInterface::STATUS_PENDING) {
                $this->addFlash('messages', 'Thanks! Comment will be published after verification.');
            } else if ($statusDefault == CommentInterface::STATUS_PUBLISHED) {
                $this->addFlash('messages', 'Thanks! Your comment has been posted.');
            }

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'result' => $comment,
                    'form' => $statusDefault == CommentInterface::STATUS_PENDING
                        ? $this->renderView('@Comments/Default/add_comment_form.html.twig', [
                            'form' => $this->createForm(AddCommentType::class, $this->commentsManager->createComment($comment->getThreadId()))->createView()
                        ]) : ''
                ], 200, [], [
                    'groups' => ['details']
                ]);
            } else {
                return $this->redirect($referer);
            }
        }

        if ($request->isXmlHttpRequest()) {
            return $this->setError([
                'success' => false,
                'error' => (string) $form->getErrors(true, false),
                'form' => $this->renderView('@Comments/Default/add_comment_form.html.twig', [
                    'form' => $form->createView()
                ])
            ]);
        } else {
            return $this->redirect($referer);
        }
    }

    /**
     * @Route("/{itemId}/update", name="comment_update", methods={"POST"})
     * @param Request $request
     * @param string $itemId
     * @param EventDispatcherInterface $eventDispatcher
     * @return Response
     * @throws \Exception
     */
    public function updateCommentAction(Request $request, $itemId, EventDispatcherInterface $eventDispatcher)
    {
        $action = $request->get('action');
        if (!$action && $request->getContent()) {
            $requestContent = json_decode($request->getContent(), true);
            if (isset($requestContent['action'])) {
                $action = $requestContent['action'];
            }
        }
        $referer = $request->headers->get('referer') . '#comments';

        if (!$this->isGranted('ROLE_ADMIN')) {
            if ($request->isXmlHttpRequest()) {
                return $this->setError([
                    'success' => false,
                    'error' => 'Forbidden.'
                ]);
            } else {
                return $this->redirect($referer);
            }
        }

        $comment = $this->commentsManager
            ->getRepository()
            ->findOneBy([
                'id' => (int) $itemId
            ]);
        if (!$comment) {
            $this->addFlash('errors', 'No comment found.');
            if ($request->isXmlHttpRequest()) {
                return $this->setError('No comment found.');
            } else {
                return $this->redirect($referer);
            }
        }

        switch ($action) {
            case 'publish':

                $comment
                    ->setStatus(CommentInterface::STATUS_PUBLISHED)
                    ->setPublishedTime(new \DateTime());
                $this->commentsManager->getEntityManager()->flush();
                
                // Dispatch event before create
                $event = new GenericEvent($comment);
                $eventDispatcher->dispatch($event, CommentInterface::COMMENT_STATUS_UPDATED)->getSubject();
                
                $this->addFlash('messages', 'Comment successfully posted.');

                break;
            case 'hide':

                $comment
                    ->setStatus(CommentInterface::STATUS_PENDING)
                    ->setPublishedTime(new \DateTime());
                $this->commentsManager->getEntityManager()->flush();

                // Dispatch event before create
                $event = new GenericEvent($comment);
                $eventDispatcher->dispatch($event, CommentInterface::COMMENT_STATUS_UPDATED)->getSubject();
                
                $this->addFlash('messages', 'Comment successfully hidden.');

                break;
            case 'reply':

                $replyContent = $request->get('reply', '');
                if (empty($replyContent) && isset($requestContent, $requestContent['reply'])) {
                    $replyContent = $requestContent['reply'];
                }
                $comment->setReply($replyContent);
                $this->commentsManager->getEntityManager()->flush();
                $this->addFlash('messages', 'Answer updated successfully.');

                break;
            case 'delete':

                $this->commentsManager->getEntityManager()->remove($comment);
                $this->commentsManager->getEntityManager()->flush();

                // Dispatch event before create
                $event = new GenericEvent($comment);
                $eventDispatcher->dispatch($event, CommentInterface::COMMENT_STATUS_UPDATED)->getSubject();
                
                $this->addFlash('messages', 'Comment deleted successfully.');

                break;
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true
            ]);
        } else {
            return $this->redirect($referer);
        }
    }

    /**
     * @Route("/{itemId}", name="comment_delete", methods={"DELETE"})
     * @param string $itemId
     * @param EventDispatcherInterface $eventDispatcher
     * @return Response
     */
    public function deleteCommentAction($itemId, EventDispatcherInterface $eventDispatcher)
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->setError([
                'success' => false,
                'error' => 'Forbidden.'
            ]);
        }
        $comment = $this->commentsManager
            ->getRepository()
            ->findOneBy([
                'id' => (int) $itemId
            ]);
        if (!$comment) {
            return $this->setError('No comment found.');
        }

        $this->commentsManager->getEntityManager()->remove($comment);
        $this->commentsManager->getEntityManager()->flush();

        // Dispatch event before create
        $event = new GenericEvent($comment);
        $eventDispatcher->dispatch($event, CommentInterface::COMMENT_STATUS_UPDATED)->getSubject();

        return $this->json([
            'success' => true
        ]);
    }

    /**
     * @Route("/{itemId}", name="comment_patch", methods={"PATCH"})
     * @param Request $request
     * @param string $itemId
     * @param EventDispatcherInterface $eventDispatcher
     * @return Response
     * @throws \Exception
     */
    public function patchCommentAction(Request $request, $itemId, EventDispatcherInterface $eventDispatcher)
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->setError([
                'success' => false,
                'error' => 'Forbidden.'
            ]);
        }
        $comment = $this->commentsManager
            ->getRepository()
            ->findOneBy([
                'id' => (int) $itemId
            ]);
        if (!$comment) {
            return $this->setError('No comment found.');
        }
        $requestContent = json_decode($request->getContent(), true);

        if (!empty($requestContent)) {
            $comment->fromArray($requestContent);
            if (isset($requestContent['status'])
                && $requestContent['status'] === CommentInterface::STATUS_PUBLISHED) {
                    $comment->setPublishedTime(new \DateTime());
            }
            $this->commentsManager->getEntityManager()->flush();

            // Dispatch event before create
            $event = new GenericEvent($comment);
            $eventDispatcher->dispatch($event, CommentInterface::COMMENT_STATUS_UPDATED)->getSubject();
        }

        return $this->json([
            'success' => true
        ]);
    }

    /**
     * @param $message
     * @param int $status
     * @return JsonResponse
     */
    public function setError($message, $status = Response::HTTP_UNPROCESSABLE_ENTITY)
    {
        $response = new JsonResponse($message);
        $response = $response->setStatusCode($status);
        return $response;
    }
}
