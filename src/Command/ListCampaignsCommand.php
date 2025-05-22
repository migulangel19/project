<?php

namespace App\Command;

use App\Entity\Campaign;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:list-campaigns',
    description: 'Lista todas las campañas existentes',
)]
class ListCampaignsCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('Este comando muestra un listado de todas las campañas con sus detalles.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Listado de Campañas');

        $repository = $this->em->getRepository(Campaign::class);
        $campaigns = $repository->findAll();

        if (empty($campaigns)) {
            $io->warning('No hay campañas registradas en el sistema.');
            return Command::SUCCESS;
        }

        $table = new Table($output);
        $table->setHeaders(['ID', 'Nombre', 'Descripción', 'Fecha Inicio', 'Fecha Fin']);

        foreach ($campaigns as $campaign) {
            $table->addRow([
                $campaign->getId(),
                $campaign->getName(),
                $this->truncateText($campaign->getDescription(), 30),
                $campaign->getStartDate()->format('Y-m-d'),
                $campaign->getEndDate()->format('Y-m-d')
            ]);
        }

        $table->render();
        $io->newLine();
        $io->success(sprintf('Total: %d campañas', count($campaigns)));

        return Command::SUCCESS;
    }

    private function truncateText(?string $text, int $length): string
    {
        if (!$text) {
            return '';
        }
        
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . '...';
    }
}