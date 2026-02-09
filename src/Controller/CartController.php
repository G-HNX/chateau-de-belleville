<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Catalog\Wine;
use App\Entity\Order\Cart;
use App\Entity\Order\CartItem;
use App\Repository\Catalog\WineRepository;
use App\Repository\Order\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/panier')]
class CartController extends AbstractController
{
    #[Route('', name: 'app_cart_index')]
    public function index(Request $request, CartRepository $cartRepository): Response
    {
        $cart = $this->getCart($request, $cartRepository);

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/ajouter/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(
        Wine $wine,
        Request $request,
        CartRepository $cartRepository,
        EntityManagerInterface $em,
    ): Response {
        if (!$wine->isAvailable()) {
            $this->addFlash('error', 'Ce vin n\'est pas disponible.');

            return $this->redirectToRoute('app_wine_index');
        }

        $quantity = max(1, $request->request->getInt('quantity', 1));

        if (!$wine->hasEnoughStock($quantity)) {
            $this->addFlash('error', 'Stock insuffisant.');

            return $this->redirectToRoute('app_wine_show', ['slug' => $wine->getSlug()]);
        }

        $cart = $this->getOrCreateCart($request, $cartRepository, $em);

        // Chercher si le vin est deja dans le panier
        $existingItem = null;
        foreach ($cart->getItems() as $item) {
            if ($item->getWine()->getId() === $wine->getId()) {
                $existingItem = $item;
                break;
            }
        }

        if ($existingItem) {
            $newQuantity = $existingItem->getQuantity() + $quantity;
            if (!$wine->hasEnoughStock($newQuantity)) {
                $this->addFlash('error', 'Stock insuffisant pour cette quantite.');

                return $this->redirectToRoute('app_cart_index');
            }
            $existingItem->setQuantity($newQuantity);
        } else {
            $cartItem = new CartItem();
            $cartItem->setWine($wine);
            $cartItem->setQuantity($quantity);
            $cart->addItem($cartItem);
        }

        $em->flush();

        $this->addFlash('success', sprintf('"%s" a ete ajoute au panier.', $wine->getName()));

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/modifier/{id}', name: 'app_cart_update', methods: ['POST'])]
    public function update(
        CartItem $cartItem,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $quantity = max(1, $request->request->getInt('quantity', 1));

        if (!$cartItem->getWine()->hasEnoughStock($quantity)) {
            $this->addFlash('error', 'Stock insuffisant.');

            return $this->redirectToRoute('app_cart_index');
        }

        $cartItem->setQuantity($quantity);
        $em->flush();

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/supprimer/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(
        CartItem $cartItem,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $cart = $cartItem->getCart();
        $cart->removeItem($cartItem);
        $em->flush();

        $this->addFlash('success', 'Article retire du panier.');

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/vider', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(
        Request $request,
        CartRepository $cartRepository,
        EntityManagerInterface $em,
    ): Response {
        $cart = $this->getCart($request, $cartRepository);

        if ($cart) {
            $cart->clear();
            $em->flush();
        }

        $this->addFlash('success', 'Le panier a ete vide.');

        return $this->redirectToRoute('app_cart_index');
    }

    private function getCart(Request $request, CartRepository $cartRepository): ?Cart
    {
        $user = $this->getUser();

        if ($user) {
            return $cartRepository->findByUser($user);
        }

        $sessionId = $request->getSession()->getId();

        return $cartRepository->findBySessionId($sessionId);
    }

    private function getOrCreateCart(
        Request $request,
        CartRepository $cartRepository,
        EntityManagerInterface $em,
    ): Cart {
        $cart = $this->getCart($request, $cartRepository);

        if ($cart) {
            return $cart;
        }

        $cart = new Cart();
        $user = $this->getUser();

        if ($user) {
            $cart->setUser($user);
        } else {
            $cart->setSessionId($request->getSession()->getId());
        }

        $em->persist($cart);

        return $cart;
    }
}
