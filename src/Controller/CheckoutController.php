<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User\User;
use App\Form\CheckoutType;
use App\Repository\Order\OrderRepository;
use App\Service\CartService;
use App\Service\OrderService;
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

        $defaultData = [
            'billingAddress' => [
                'firstName' => $user?->getFirstName() ?? '',
                'lastName' => $user?->getLastName() ?? '',
                'street' => '',
                'zipCode' => '',
                'city' => '',
                'country' => 'FR',
            ],
            'shippingAddress' => [
                'firstName' => '',
                'lastName' => '',
                'street' => '',
                'zipCode' => '',
                'city' => '',
                'country' => 'FR',
            ],
        ];

        $form = $this->createForm(CheckoutType::class, $defaultData, [
            'is_guest' => $isGuest,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $outOfStockWine = $this->orderService->checkCartStock($cart);
            if ($outOfStockWine) {
                $this->addFlash('error', sprintf('Stock insuffisant pour "%s".', $outOfStockWine));

                return $this->redirectToRoute('app_cart_index');
            }

            $formData = [
                'billingAddress' => $form->get('billingAddress')->getData(),
                'shippingAddress' => $form->get('shippingAddress')->getData(),
                'notes' => $form->get('notes')->getData(),
            ];

            if ($isGuest) {
                $formData['email'] = $form->get('email')->getData();
                $formData['firstName'] = $form->get('firstName')->getData();
                $formData['lastName'] = $form->get('lastName')->getData();
                $formData['phone'] = $form->get('phone')->getData();
            }

            $order = $this->orderService->createOrderFromCart($cart, $user, $formData);

            $this->addFlash('success', sprintf(
                'Votre commande %s a bien été enregistrée.',
                $order->getReference(),
            ));

            return $this->redirectToRoute('app_checkout_confirmation', [
                'reference' => $order->getReference(),
            ]);
        }

        return $this->render('checkout/index.html.twig', [
            'cart' => $cart,
            'form' => $form,
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
