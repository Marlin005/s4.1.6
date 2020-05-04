<?php

namespace Andchir\ImportExportBundle\Command;

use App\MainBundle\Document\Category;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class ImportCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('app:import')
            ->setDescription('Import documents from XLS/XLSX/CSV.')
            ->setHelp('Available actions: filters_update')
            ->addArgument('configId', InputArgument::REQUIRED, 'Import configuration ID.')
            ->addArgument('parentId', InputArgument::OPTIONAL, 'Parent category ID.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $configId = $input->getArgument('configId');
        $parentId = $input->getArgument('parentId');

        var_dump($configId, $parentId);

    }
}

