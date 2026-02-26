<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\Order\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Anonymise les données personnelles sensibles des anciennes commandes (RGPD).
 *
 * Usage :
 *   php bin/console app:gdpr:anonymize-orders
 *   php bin/console app:gdpr:anonymize-orders --retention-years=3
 *
 * À planifier en cron mensuel, ex. :
 *   0 3 1 * * php /path/to/app/bin/console app:gdpr:anonymize-orders
 */
#[AsCommand(
    name: 'app:gdpr:anonymize-orders',
    description: 'Anonymise la date de naissance des commandes dépassant la durée de conservation RGPD.',
)]
class GdprAnonymizeOrdersCommand extends Command
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'retention-years',
            null,
            InputOption::VALUE_OPTIONAL,
            'Durée de conservation en années avant anonymisation.',
            5
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $retentionYears = (int) $input->getOption('retention-years');

        $cutoffDate = new \DateTime("-{$retentionYears} years");
        $io->info(sprintf(
            'Anonymisation des commandes antérieures au %s (%d ans de rétention).',
            $cutoffDate->format('d/m/Y'),
            $retentionYears
        ));

        $orders = $this->orderRepository->findOrdersWithBirthDateBefore($cutoffDate);

        if (empty($orders)) {
            $io->success('Aucune commande à anonymiser.');

            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($orders as $order) {
            $order->setCustomerBirthDate(null);
            ++$count;
        }

        $this->em->flush();

        $io->success(sprintf('%d commande(s) anonymisée(s).', $count));

        return Command::SUCCESS;
    }
}
