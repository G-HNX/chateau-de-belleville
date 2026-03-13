<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener(event: LoginSuccessEvent::class)]
/**
 * Listener de connexion réussie.
 * Met à jour la date de dernière connexion de l'utilisateur
 * à chaque authentification réussie.
 */
class LoginSuccessListener
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    /** Enregistre la date/heure de connexion sur l'entité User. */
    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $user->setLastLoginAt(new \DateTime());
        $this->em->flush();
    }
}
