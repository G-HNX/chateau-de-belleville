<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User\User;
use App\Form\CheckoutType;
use App\Repository\Order\OrderRepository;
use App\Service\CartService;
use App\Service\OrderService;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/commande')]
class CheckoutController extends AbstractController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly OrderService $orderService,
        private readonly StripeService $stripeService,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'app_checkout_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        $cart = $this->cartService->getCart($user);

        if (!$cart || $cart->isEmpty()) {
            $this->addFlash('warning', 'Votre panier est vide.');

            return $this->redirectToRoute('app_cart_index');
        }

        $isGuest = !$user;

        $billingDefault = [
            'firstName' => $user?->getFirstName() ?? '',
            'lastName' => $user?->getLastName() ?? '',
            'street' => '',
            'zipCode' => '',
            'city' => '',
            'country' => 'FR',
        ];
        $shippingDefault = [
            'firstName' => '',
            'lastName' => '',
            'street' => '',
            'zipCode' => '',
            'city' => '',
            'country' => 'FR',
        ];

        if ($user) {
            $billingAddress = $user->getDefaultBillingAddress();
            if ($billingAddress) {
                $billingDefault = [
                    'firstName' => $billingAddress->getFirstName(),
                    'lastName' => $billingAddress->getLastName(),
                    'street' => $billingAddress->getStreet(),
                    'zipCode' => $billingAddress->getPostalCode(),
                    'city' => $billingAddress->getCity(),
                    'country' => $billingAddress->getCountry(),
                ];
            }

            $shippingAddress = $user->getDefaultShippingAddress();
            if ($shippingAddress) {
                $shippingDefault = [
                    'firstName' => $shippingAddress->getFirstName(),
                    'lastName' => $shippingAddress->getLastName(),
                    'street' => $shippingAddress->getStreet(),
                    'zipCode' => $shippingAddress->getPostalCode(),
                    'city' => $shippingAddress->getCity(),
                    'country' => $shippingAddress->getCountry(),
                ];
            }
        }

        $defaultData = [
            'billingAddress' => $billingDefault,
            'shippingAddress' => $shippingDefault,
        ];

        $form = $this->createForm(CheckoutType::class, $defaultData, [
            'is_guest' => $isGuest,
            'birthDate' => $user?->getBirthDate(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $outOfStockWine = $this->orderService->checkCartStock($cart);
            if ($outOfStockWine) {
                $this->addFlash('error', sprintf('Stock insuffisant pour "%s".', $outOfStockWine));

                return $this->redirectToRoute('app_cart_index');
            }

            $billingAddress = $form->get('billingAddress')->getData();
            $sameAsBilling = $form->get('sameAsBilling')->getData();

            $formData = [
                'billingAddress' => $billingAddress,
                'shippingAddress' => $sameAsBilling ? $billingAddress : $form->get('shippingAddress')->getData(),
                'notes' => $form->get('notes')->getData(),
            ];

            if ($isGuest) {
                $formData['email'] = $form->get('email')->getData();
                $formData['firstName'] = $form->get('firstName')->getData();
                $formData['lastName'] = $form->get('lastName')->getData();
                $formData['phone'] = $form->get('phone')->getData();
            }

            /** @var \DateTimeInterface $birthDate */
            $birthDate = $form->get('birthDate')->getData();
            $age = (new \DateTime())->diff($birthDate)->y;

            if ($age < 18) {
                $this->addFlash('error', 'Vous devez avoir 18 ans ou plus pour passer commande sur notre site.');

                return $this->render('checkout/index.html.twig', [
                    'cart' => $cart,
                    'form' => $form,
                ]);
            }

            if ($user && $user->getBirthDate() === null) {
                $user->setBirthDate($birthDate);
                $this->em->flush();
            }

            $order = $this->orderService->createOrderFromCart($cart, $user, $formData, $birthDate);

            return $this->redirectToRoute('app_checkout_payment', [
                'reference' => $order->getReference(),
            ]);
        }

        return $this->render('checkout/index.html.twig', [
            'cart' => $cart,
            'form' => $form,
        ]);
    }

    #[Route('/paiement/{reference}', name: 'app_checkout_payment')]
    public function payment(string $reference, OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->findByReference($reference);

        if (!$order) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        if ($order->getStatus() !== \App\Enum\OrderStatus::PENDING) {
            return $this->redirectToRoute('app_checkout_confirmation', [
                'reference' => $order->getReference(),
            ]);
        }

        try {
            $paymentIntent = $this->stripeService->createPaymentIntent($order);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Le service de paiement est temporairement indisponible. Votre commande a bien été enregistrée.');

            return $this->redirectToRoute('app_checkout_confirmation', [
                'reference' => $order->getReference(),
            ]);
        }

        $order->setStripePaymentIntentId($paymentIntent->id);
        $this->orderService->save($order);

        return $this->render('checkout/payment.html.twig', [
            'order' => $order,
            'clientSecret' => $paymentIntent->client_secret,
            'stripePublicKey' => $this->getParameter('stripe_public_key'),
        ]);
    }

    #[Route('/confirmation/{reference}', name: 'app_checkout_confirmation')]
    public function confirmation(string $reference, OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->findByReference($reference);

        if (!$order) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        return $this->render('checkout/confirmation.html.twig', [
            'order' => $order,
        ]);
    }
}
