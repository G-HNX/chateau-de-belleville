<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\Booking\ReservationRepository;
use App\Repository\Order\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/compte')]
#[IsGranted('ROLE_USER')]
class AccountController extends AbstractController
{
    #[Route('', name: 'app_account_index')]
    public function index(
        OrderRepository $orderRepository,
        ReservationRepository $reservationRepository,
    ): Response {
        $user = $this->getUser();

        return $this->render('account/index.html.twig', [
            'user' => $user,
            'recentOrders' => $orderRepository->findByCustomer($user),
            'upcomingReservations' => $reservationRepository->findByUser($user),
        ]);
    }

    #[Route('/commandes', name: 'app_account_orders')]
    public function orders(OrderRepository $orderRepository): Response
    {
        return $this->render('account/orders.html.twig', [
            'orders' => $orderRepository->findByCustomer($this->getUser()),
        ]);
    }

    #[Route('/commandes/{reference}', name: 'app_account_order_show')]
    public function orderShow(string $reference, OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->findByReference($reference);

        if (!$order || $order->getCustomer() !== $this->getUser()) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        return $this->render('account/order_show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/reservations', name: 'app_account_reservations')]
    public function reservations(ReservationRepository $reservationRepository): Response
    {
        return $this->render('account/reservations.html.twig', [
            'reservations' => $reservationRepository->findByUser($this->getUser()),
        ]);
    }

    #[Route('/profil', name: 'app_account_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $user->setFirstName($request->request->get('firstName', ''));
            $user->setLastName($request->request->get('lastName', ''));
            $user->setPhone($request->request->get('phone'));

            $em->flush();

            $this->addFlash('success', 'Profil mis a jour.');

            return $this->redirectToRoute('app_account_profile');
        }

        return $this->render('account/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/mot-de-passe', name: 'app_account_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
    ): Response {
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $currentPassword = $request->request->get('currentPassword', '');
            $newPassword = $request->request->get('newPassword', '');
            $confirmPassword = $request->request->get('confirmPassword', '');

            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect.');

                return $this->redirectToRoute('app_account_password');
            }

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');

                return $this->redirectToRoute('app_account_password');
            }

            if (strlen($newPassword) < 6) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caracteres.');

                return $this->redirectToRoute('app_account_password');
            }

            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $em->flush();

            $this->addFlash('success', 'Mot de passe modifie.');

            return $this->redirectToRoute('app_account_index');
        }

        return $this->render('account/password.html.twig');
    }
}
