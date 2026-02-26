<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\User\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/customers')]
#[IsGranted('ROLE_ADMIN')]
class CustomerExportController extends AbstractController
{
    #[Route('/export.csv', name: 'admin_customers_export_csv', methods: ['GET'])]
    public function exportCsv(UserRepository $userRepository, LoggerInterface $logger): StreamedResponse
    {
        $logger->info('Export CSV clients déclenché.', [
            'admin' => $this->getUser()?->getUserIdentifier(),
        ]);
        $rows = $userRepository->findForExport();

        $filename = sprintf('clients-%s.csv', date('Y-m-d'));

        $response = new StreamedResponse(static function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 pour compatibilité Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Prénom',
                'Nom',
                'Email',
                'Téléphone',
                'Date inscription',
                'Dernière connexion',
                'Email vérifié',
                'Newsletter',
                'Nb commandes',
                'Total dépensé (€)',
            ], ';');

            foreach ($rows as $row) {
                $user       = $row[0];
                $orderCount = (int) $row['orderCount'];
                $totalSpent = (int) $row['totalSpent'];

                fputcsv($handle, [
                    $user->getFirstName(),
                    $user->getLastName(),
                    $user->getEmail(),
                    $user->getPhone() ?? '',
                    $user->getCreatedAt()?->format('d/m/Y') ?? '',
                    $user->getLastLoginAt()?->format('d/m/Y H:i') ?? '',
                    $user->isVerified() ? 'Oui' : 'Non',
                    $user->isNewsletterOptIn() ? 'Oui' : 'Non',
                    $orderCount,
                    number_format($totalSpent / 100, 2, '.', ''),
                ], ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }
}
