<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Enum\OrderStatus;
use App\Repository\Order\OrderRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/orders')]
#[IsGranted('ROLE_ADMIN')]
class OrderExportController extends AbstractController
{
    #[Route('/export.csv', name: 'admin_orders_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request, OrderRepository $orderRepository, LoggerInterface $logger): StreamedResponse
    {
        $logger->info('Export CSV commandes déclenché.', [
            'admin' => $this->getUser()?->getUserIdentifier(),
            'filters' => $request->query->all(),
        ]);
        $from = null;
        $to   = null;

        if ($fromStr = $request->query->get('from')) {
            $from = \DateTime::createFromFormat('Y-m-d', $fromStr);
            $from = $from ?: null;
        }

        if ($toStr = $request->query->get('to')) {
            $to = \DateTime::createFromFormat('Y-m-d', $toStr);
            if ($to) {
                $to->setTime(23, 59, 59);
            } else {
                $to = null;
            }
        }

        $statusValue = $request->query->get('status');
        $status = $statusValue ? OrderStatus::from($statusValue) : null;

        $orders = $orderRepository->findForExport($from, $to, $status);

        $filename = sprintf('commandes-%s.csv', date('Y-m-d'));

        $response = new StreamedResponse(static function () use ($orders): void {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 pour compatibilité Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Référence',
                'Date commande',
                'Statut',
                'Prénom client',
                'Nom client',
                'Email',
                'Téléphone',
                'Vin',
                'Millésime',
                'Quantité',
                'Prix unitaire HT (€)',
                'Total ligne HT (€)',
                'Sous-total commande HT (€)',
                'TVA (€)',
                'Livraison (€)',
                'Total TTC (€)',
                'Date paiement',
                'Date expédition',
                'N° suivi',
                'Transporteur',
                'Ville de livraison',
            ], ';');

            foreach ($orders as $order) {
                $shippingCity = $order->getShippingAddress()['city'] ?? '';
                $paidAt       = $order->getPaidAt()?->format('d/m/Y') ?? '';
                $shippedAt    = $order->getShippedAt()?->format('d/m/Y') ?? '';

                foreach ($order->getItems() as $item) {
                    fputcsv($handle, [
                        $order->getReference(),
                        $order->getCreatedAt()?->format('d/m/Y H:i'),
                        $order->getStatus()->label(),
                        $order->getCustomerFirstName(),
                        $order->getCustomerLastName(),
                        $order->getCustomerEmail(),
                        $order->getCustomerPhone() ?? '',
                        $item->getWineName(),
                        $item->getWineVintage() ?? '',
                        $item->getQuantity(),
                        number_format($item->getUnitPrice(), 2, '.', ''),
                        number_format($item->getTotal(), 2, '.', ''),
                        number_format($order->getSubtotal(), 2, '.', ''),
                        number_format($order->getTaxAmount(), 2, '.', ''),
                        number_format($order->getShippingCost(), 2, '.', ''),
                        number_format($order->getTotal(), 2, '.', ''),
                        $paidAt,
                        $shippedAt,
                        $order->getTrackingNumber() ?? '',
                        $order->getCarrier() ?? '',
                        $shippingCity,
                    ], ';');
                }
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }
}
