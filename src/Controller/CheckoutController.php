<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Order\Order;
use App\Entity\Order\OrderItem;
use App\Repository\Order\CartRepository;
use App\Repository\Order\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/commande')]
class CheckoutController extends AbstractController
{
    #[Route('', name: 'app_checkout_index')]
    public function index(Request $request, CartRepository $cartRepository): Response
    {
        $cart = $this->getCart($request, $cartRepository);

        if (!$cart || $cart->isEmpty()) {
            $this->addFlash('warning', 'Votre panier est vide.');

            return $this->redirectToRoute('app_cart_index');
        }

        return $this->render('checkout/index.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/valider', name: 'app_checkout_process', methods: ['POST'])]
    public function process(
        Request $request,
        CartRepository $cartRepository,
        EntityManagerInterface $em,
    ): Response {
        $cart = $this->getCart($request, $cartRepository);

        if (!$cart || $cart->isEmpty()) {
            $this->addFlash('warning', 'Votre panier est vide.');

            return $this->redirectToRoute('app_cart_index');
        }

        // Verifier le stock de chaque article
        foreach ($cart->getItems() as $item) {
            if (!$item->getWine()->hasEnoughStock($item->getQuantity())) {
                $this->addFlash('error', sprintf(
                    'Stock insuffisant pour "%s".',
                    $item->getWine()->getName(),
                ));

                return $this->redirectToRoute('app_cart_index');
            }
        }

        // Creer la commande
        $order = new Order();
        $user = $this->getUser();

        if ($user) {
            $order->setCustomer($user);
            $order->setCustomerEmail($user->getEmail());
            $order->setCustomerFirstName($user->getFirstName());
            $order->setCustomerLastName($user->getLastName());
            $order->setCustomerPhone($user->getPhone());
        } else {
            $order->setCustomerEmail($request->request->get('email', ''));
            $order->setCustomerFirstName($request->request->get('firstName', ''));
            $order->setCustomerLastName($request->request->get('lastName', ''));
            $order->setCustomerPhone($request->request->get('phone'));
        }

        // Adresses
        $order->setBillingAddress([
            'firstName' => $request->request->get('billing_firstName', ''),
            'lastName' => $request->request->get('billing_lastName', ''),
            'street' => $request->request->get('billing_street', ''),
            'zipCode' => $request->request->get('billing_zipCode', ''),
            'city' => $request->request->get('billing_city', ''),
            'country' => $request->request->get('billing_country', 'FR'),
        ]);

        $order->setShippingAddress([
            'firstName' => $request->request->get('shipping_firstName', ''),
            'lastName' => $request->request->get('shipping_lastName', ''),
            'street' => $request->request->get('shipping_street', ''),
            'zipCode' => $request->request->get('shipping_zipCode', ''),
            'city' => $request->request->get('shipping_city', ''),
            'country' => $request->request->get('shipping_country', 'FR'),
        ]);

        $order->setCustomerNotes($request->request->get('notes'));

        // Convertir les articles du panier en lignes de commande
        foreach ($cart->getItems() as $cartItem) {
            $order->addItem(OrderItem::createFromCartItem($cartItem));
            $cartItem->getWine()->decrementStock($cartItem->getQuantity());
        }

        $order->calculateTotals();

        $em->persist($order);

        // Vider le panier
        $cart->clear();

        $em->flush();

        $this->addFlash('success', sprintf(
            'Votre commande %s a bien ete enregistree.',
            $order->getReference(),
        ));

        return $this->redirectToRoute('app_checkout_confirmation', [
            'reference' => $order->getReference(),
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

    private function getCart(Request $request, CartRepository $cartRepository): ?\App\Entity\Order\Cart
    {
        $user = $this->getUser();

        if ($user) {
            return $cartRepository->findByUser($user);
        }

        return $cartRepository->findBySessionId($request->getSession()->getId());
    }
}
