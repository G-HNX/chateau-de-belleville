<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\Order\OrderRepository;
use App\Service\PdfService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/order')]
#[IsGranted('ROLE_ADMIN')]
class OrderPdfController extends AbstractController
{
    public function __construct(
        private readonly PdfService $pdfService,
        private readonly OrderRepository $orderRepository,
    ) {}

    #[Route('/{id}/invoice', name: 'admin_order_invoice', methods: ['GET'])]
    public function invoice(int $id): Response
    {
        $order = $this->orderRepository->find($id);
        if (!$order) {
            throw $this->createNotFoundException();
        }

        $pdf = $this->pdfService->generateInvoice($order);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="facture-%s.pdf"', $order->getReference()),
        ]);
    }

    #[Route('/{id}/order-slip', name: 'admin_order_slip', methods: ['GET'])]
    public function orderSlip(int $id): Response
    {
        $order = $this->orderRepository->find($id);
        if (!$order) {
            throw $this->createNotFoundException();
        }

        $pdf = $this->pdfService->generateOrderSlip($order);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="bon-commande-%s.pdf"', $order->getReference()),
        ]);
    }
}
