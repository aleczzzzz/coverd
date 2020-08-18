<?php

namespace App\EventSubscriber\Workflow;

use App\Entity\Client;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Workflow\Event\GuardEvent;

class ClientSubscriber implements EventSubscriberInterface
{
    /** @var AuthorizationCheckerInterface */
    protected $checker;

    public function __construct(AuthorizationCheckerInterface $checker)
    {
        $this->checker = $checker;
    }

    public function onTransition(GuardEvent $event): void
    {
        if (!$this->checker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $event->setBlocked(true);
        }
    }

    public function onTransitionActivate(GuardEvent $event): void
    {
        if (!$this->checker->isGranted(Client::ROLE_MANAGE_OWN) &&
            !$this->checker->isGranted(Client::ROLE_EDIT_ALL)) {
            $event->setBlocked(true);
        }
    }

    public function onTransitionDeactivate(GuardEvent $event): void
    {
        if (!$this->checker->isGranted(Client::ROLE_MANAGE_OWN) &&
            !$this->checker->isGranted(Client::ROLE_EDIT_ALL)) {
            $event->setBlocked(true);
        }
    }

    public function onTransitionExpire(GuardEvent $event): void
    {
        if (!$this->checker->isGranted(Client::ROLE_EDIT_ALL)) {
            $event->setBlocked(true);
        }
    }

    public function onTransitionDuplicateInactive(GuardEvent $event): void
    {
        if (!$this->checker->isGranted(Client::ROLE_EDIT_ALL)) {
            $event->setBlocked(true);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            'workflow.client_management.guard' => 'onTransition',
            'workflow.client_management.guard.ACTIVATE' => 'onTransitionActivate',
            'workflow.client_management.guard.DEACTIVATE' => 'onTransitionDeactivate',
            'workflow.client_management.guard.EXPIRE' => 'onTransitionExpire',
            'workflow.client_management.guard.DUPLICATE' => 'onTransitionDuplicateInactive',
        ];
    }
}
