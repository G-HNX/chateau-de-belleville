<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Catalog\Wine;
use App\Entity\Order\CartItem;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/panier')]
class CartController extends AbstractController
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    #[Route('', name: 'app_cart_index')]
    public function index(): Response
    {
        $cart = $this->cartService->getCart($this->getUser());

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/ajouter/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(Wine $wine, Request $request): Response
    {
        $isAjax = $request->headers->get('Accept') === 'application/json';

        if (!$this->isCsrfTokenValid('cart_add', $request->request->get('_token'))) {
            if ($isAjax) {
                return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalide.'], 403);
            }
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $quantity = max(1, $request->request->getInt('quantity', 1));

        $error = $this->cartService->addWine($this->getUser(), $wine, $quantity);

        if ($isAjax) {
            if ($error) {
                return new JsonResponse(['success' => false, 'message' => $error], 422);
            }

            $cart = $this->cartService->getCart($this->getUser());

            return new JsonResponse([
                'success' => true,
                'message' => sprintf('"%s" a été ajouté au panier.', $wine->getName()),
                'cartCount' => $cart ? $cart->getItemsCount() : 0,
            ]);
        }

        if ($error) {
            $this->addFlash('error', $error);

            return $this->redirectToRoute('app_wine_show', ['slug' => $wine->getSlug()]);
        }

        $this->addFlash('success', sprintf('"%s" a ete ajoute au panier.', $wine->getName()));

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/modifier/{id}', name: 'app_cart_update', methods: ['POST'])]
    public function update(CartItem $cartItem, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('cart_update', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $cart = $this->cartService->getCart($this->getUser());
        if (!$cart || $cartItem->getCart() !== $cart) {
            throw $this->createAccessDeniedException();
        }

        $quantity = max(1, $request->request->getInt('quantity', 1));

        $error = $this->cartService->updateItemQuantity($cartItem, $quantity);

        if ($error) {
            $this->addFlash('error', $error);
        }

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/supprimer/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(CartItem $cartItem, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('cart_remove', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $cart = $this->cartService->getCart($this->getUser());
        if (!$cart || $cartItem->getCart() !== $cart) {
            throw $this->createAccessDeniedException();
        }

        $this->cartService->removeItem($cartItem);

        $this->addFlash('success', 'Article retire du panier.');

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/vider', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('cart_clear', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $this->cartService->clearCart($this->getUser());

        $this->addFlash('success', 'Le panier a ete vide.');

        return $this->redirectToRoute('app_cart_index');
    }
}
