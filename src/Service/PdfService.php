<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order\Order;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

/**
 * Service de génération de documents PDF.
 *
 * Utilise Dompdf pour convertir des templates Twig en fichiers PDF.
 * Génère les factures et les bons de commande liés aux commandes.
 */
class PdfService
{
    public function __construct(
        private readonly Environment $twig,
    ) {}

    /**
     * Génère la facture PDF d'une commande. Retourne le contenu binaire du PDF.
     */
    public function generateInvoice(Order $order): string
    {
        $html = $this->twig->render('pdf/invoice.html.twig', [
            'order' => $order,
        ]);

        return $this->renderPdf($html);
    }

    /**
     * Génère le bon de commande PDF. Retourne le contenu binaire du PDF.
     */
    public function generateOrderSlip(Order $order): string
    {
        $html = $this->twig->render('pdf/order_slip.html.twig', [
            'order' => $order,
        ]);

        return $this->renderPdf($html);
    }

    /**
     * Convertit du HTML en PDF via Dompdf. Méthode interne partagée par les générateurs.
     */
    private function renderPdf(string $html): string
    {
        // Configuration Dompdf : HTML5 activé, accès distant désactivé (sécurité)
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
