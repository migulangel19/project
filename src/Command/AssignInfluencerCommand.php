<?php

namespace App\Command;

use App\Entity\Campaign;
use App\Entity\Influencer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:assign-influencer',
    description: 'Asigna un influencer a una campaña',
)]
class AssignInfluencerCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('campaign-id', InputArgument::REQUIRED, 'ID de la campaña')
            ->addArgument('influencer-id', InputArgument::REQUIRED, 'ID del influencer')
            ->setHelp('Este comando asigna un influencer a una campaña específica.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $campaignId = $input->getArgument('campaign-id');
        $influencerId = $input->getArgument('influencer-id');

        // Miramos sin las ids son numericas (lo van a ser)
        if (!is_numeric($campaignId) || !is_numeric($influencerId)) {
            $io->error('Los IDs deben ser valores numéricos.');
            return Command::FAILURE;
        }

        // Buscamos la campaña
        $campaign = $this->em->getRepository(Campaign::class)->find($campaignId);
        if (!$campaign) {
            $io->error(sprintf('No se encontró ninguna campaña con el ID %s.', $campaignId));
            return Command::FAILURE;
        }

        // Buscaamos el influencer
        $influencer = $this->em->getRepository(Influencer::class)->find($influencerId);
        if (!$influencer) {
            $io->error(sprintf('No se encontró ningún influencer con el ID %s.', $influencerId));
            return Command::FAILURE;
        }

        try {
            
            $campaign->addInfluencer($influencer);
            
       

            // Guardar los cambios
            $this->em->flush();

            $io->success(sprintf(
                'El influencer "%s" ha sido asignado correctamente a la campaña "%s".',
                $influencer->getName(), 
                $campaign->getName()
            ));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Ha ocurrido un error al asignar el influencer a la campaña: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}