<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order\Order;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfService
{
    public function __construct(
        private readonly Environment $twig,
    ) {}

    public function generateInvoice(Order $order): string
    {
        $html = $this->twig->render('pdf/invoice.html.twig', [
            'order' => $order,
        ]);

        return $this->renderPdf($html);
    }

    public function generateOrderSlip(Order $order): string
    {
        $html = $this->twig->render('pdf/order_slip.html.twig', [
            'order' => $order,
        ]);

        return $this->renderPdf($html);
    }

    private function renderPdf(string $html): string
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
