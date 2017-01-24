<?php

namespace MZA\HostsManager\Command;

use MZA\HostsManager\Service\HostsManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class HostsListCommand extends HostsCommand
{
    const HOSTS_FILE_WIN = 'C:\Windows\System32\drivers\etc\hosts';
    const HOSTS_FILE_UNX = '/etc/hosts';

    /**
     * @var InputInterface
     */
    protected $input;
    /**
     * @var OutputInterface
     */
    protected $output;


    protected function configure()
    {
        $this
            ->setName('hosts:list')
            ->setDescription('List hosts file content.')
            ->addOption('raw', 'r', InputOption::VALUE_NONE, 'Output hosts file content generated.')
            ->addOption('console', 'c', InputOption::VALUE_OPTIONAL, 'Output hosts structure in console after process.', 1);

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        if ($input->getOption('raw')) {
            $output->writeln($this->hostsManager->asString());
        } else {
            // Output without check if option "console is given"
            $this->outputStructuredHosts($input, $output);
        }

    }
}
