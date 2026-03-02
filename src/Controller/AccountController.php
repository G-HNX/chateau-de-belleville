<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Customer\Address;
use App\Entity\NewsletterSubscriber;
use App\Entity\User\User;
use App\Form\AddressType;
use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use App\Repository\Booking\ReservationRepository;
use App\Repository\Customer\AddressRepository;
use App\Repository\NewsletterSubscriberRepository;
use App\Repository\Order\OrderRepository;
use App\Service\PdfService;
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
        /** @var User $user */
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
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('account/orders.html.twig', [
            'orders' => $orderRepository->findByCustomer($user),
        ]);
    }

    #[Route('/commandes/{reference}', name: 'app_account_order_show')]
    public function orderShow(string $reference, OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->findByReference($reference);

        /** @var User $user */
        $user = $this->getUser();

        if (!$order || $order->getCustomer() !== $user) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        return $this->render('account/order_show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/commandes/{reference}/facture', name: 'app_account_order_invoice', methods: ['GET'])]
    public function orderInvoice(string $reference, OrderRepository $orderRepository, PdfService $pdfService): Response
    {
        $order = $orderRepository->findByReference($reference);

        /** @var User $user */
        $user = $this->getUser();

        if (!$order || $order->getCustomer() !== $user) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        $pdf = $pdfService->generateInvoice($order);

        return new Response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="facture-' . $order->getReference() . '.pdf"',
        ]);
    }

    #[Route('/reservations', name: 'app_account_reservations')]
    public function reservations(ReservationRepository $reservationRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('account/reservations.html.twig', [
            'reservations' => $reservationRepository->findByUser($user),
        ]);
    }

    #[Route('/adresses', name: 'app_account_addresses')]
    public function addresses(AddressRepository $addressRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('account/addresses.html.twig', [
            'addresses' => $addressRepository->findByUser($user),
        ]);
    }

    #[Route('/adresses/ajouter', name: 'app_account_address_add', methods: ['GET', 'POST'])]
    public function addAddress(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $address = new Address();
        $address->setUser($user);

        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleDefaultFlags($address, $user);
            $em->persist($address);
            $em->flush();

            $this->addFlash('success', 'Adresse ajoutée.');

            return $this->redirectToRoute('app_account_addresses');
        }

        return $this->render('account/address_form.html.twig', [
            'form' => $form,
            'title' => 'Ajouter une adresse',
        ]);
    }

    #[Route('/adresses/{id}/modifier', name: 'app_account_address_edit', methods: ['GET', 'POST'])]
    public function editAddress(Address $address, Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($address->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleDefaultFlags($address, $user);
            $em->flush();

            $this->addFlash('success', 'Adresse modifiée.');

            return $this->redirectToRoute('app_account_addresses');
        }

        return $this->render('account/address_form.html.twig', [
            'form' => $form,
            'title' => 'Modifier l\'adresse',
        ]);
    }

    #[Route('/adresses/{id}/supprimer', name: 'app_account_address_delete', methods: ['POST'])]
    public function deleteAddress(Address $address, Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($address->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('delete-address-' . $address->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($address);
        $em->flush();

        $this->addFlash('success', 'Adresse supprimée.');

        return $this->redirectToRoute('app_account_addresses');
    }

    #[Route('/newsletter', name: 'app_account_newsletter_toggle', methods: ['POST'])]
    public function toggleNewsletter(
        Request $request,
        EntityManagerInterface $em,
        NewsletterSubscriberRepository $subscriberRepo,
    ): Response {
        if (!$this->isCsrfTokenValid('newsletter_toggle', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();
        $newOptIn = !$user->isNewsletterOptIn();
        $user->setNewsletterOptIn($newOptIn);

        $existing = $subscriberRepo->findOneBy(['email' => $user->getEmail()]);
        if ($newOptIn && $existing === null) {
            $em->persist((new NewsletterSubscriber())->setEmail($user->getEmail()));
        } elseif (!$newOptIn && $existing !== null) {
            $em->remove($existing);
        }

        $em->flush();

        $this->addFlash('success', $newOptIn
            ? 'Vous êtes inscrit(e) à la newsletter.'
            : 'Vous avez été désinscrit(e) de la newsletter.'
        );

        return $this->redirectToRoute('app_account_profile');
    }

    #[Route('/profil', name: 'app_account_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Profil mis à jour.');

            return $this->redirectToRoute('app_account_profile');
        }

        return $this->render('account/profile.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/mes-vins', name: 'app_account_favorites')]
    public function favorites(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('account/favorites.html.twig', [
            'favorites' => $user->getFavoriteWines(),
        ]);
    }

    #[Route('/mot-de-passe', name: 'app_account_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();

            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect.');

                return $this->redirectToRoute('app_account_password');
            }

            $newPassword = $form->get('newPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $em->flush();

            $this->addFlash('success', 'Mot de passe modifié.');

            return $this->redirectToRoute('app_account_index');
        }

        return $this->render('account/password.html.twig', [
            'form' => $form,
        ]);
    }

    private function handleDefaultFlags(Address $address, User $user): void
    {
        if ($address->isDefaultShipping()) {
            foreach ($user->getAddresses() as $other) {
                if ($other !== $address && $other->isDefaultShipping()) {
                    $other->setIsDefaultShipping(false);
                }
            }
        }
        if ($address->isDefaultBilling()) {
            foreach ($user->getAddresses() as $other) {
                if ($other !== $address && $other->isDefaultBilling()) {
                    $other->setIsDefaultBilling(false);
                }
            }
        }
    }
}
