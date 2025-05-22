<?php

namespace App\Command;

use App\Entity\Campaign;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;

#[AsCommand(
    name: 'app:create-campaign',
    description: 'Crea una nueva campaña de marketing',
)]
class CreateCampaignCommand extends Command
{
    private $em;
    private $dateFormat = 'Y-m-d';

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('Este comando te permite crear una nueva campaña solicitando los datos necesarios.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Creación de nueva campaña');
        
        $helper = $this->getHelper('question');
        
        // Solicitamos datos por terminal
        $nameQ = new Question('Nombre de la campaña: ');
        $nameQ->setValidator(function ($value) {
            if (trim($value) === '') {
                throw new \Exception('El nombre no puede estar vacío');
            }
            return $value;
        });
        
        $descQ = new Question('Descripción: ');
        $startQ = new Question('Fecha de inicio (YYYY-MM-DD): ', date($this->dateFormat));
        $startQ->setValidator(function ($value) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                throw new \Exception('Formato de fecha inválido. Usa YYYY-MM-DD');
            }
            return $value;
        });
        
        $endQ = new Question('Fecha de fin (YYYY-MM-DD): ', date($this->dateFormat, strtotime('+30 days')));
        $endQ->setValidator(function ($value) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                throw new \Exception('Formato de fecha inválido. Usa YYYY-MM-DD');
            }
            return $value;
        });

        try {
            $nombre = $helper->ask($input, $output, $nameQ);
            $desc = $helper->ask($input, $output, $descQ);
            $fechaInicio = $helper->ask($input, $output, $startQ);
            $fechaFin = $helper->ask($input, $output, $endQ);
            
            // Validar fechas
            $startDate = new \DateTime($fechaInicio);
            $endDate = new \DateTime($fechaFin);
            //Miramos que la fecha posterior no sea mayor a la de inicio 
            //Podria ser igual si es una campaña de un dia
            if ($endDate < $startDate) {
                $io->error('Fecha fin anterior a fecha inio');
                return Command::FAILURE;
            }
            
            // Crear y guardar la campaña
            $campaign = new Campaign();
            $campaign->setName($nombre);
            $campaign->setDescription($desc ?: '');
            $campaign->setStartDate($startDate);
            $campaign->setEndDate($endDate);
            
            $this->em->persist($campaign);
            $this->em->flush();
            
            $io->success(sprintf('Campaña "%s" creada correctamente', $nombre));
            return Command::SUCCESS;
            
        } catch (\Exception $ex) {
            $io->error($ex->getMessage());
            return Command::FAILURE;
        }
    }
}