<?php

namespace MZA\HostsManager\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class HostsRemoveCommand extends HostsCommand
{
    protected function configure()
    {
        $this
            ->setName('hosts:remove')
            ->setDescription('Remove hostname from local hosts file system.')
            ->addArgument('name', InputArgument::OPTIONAL, 'Hostname to remove, multiple hosts allowed (separated by , )');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $helper = $this->getHelper('question');

        $names = $input->getArgument('name');
        while (!$names) {
            $question = new Question('<fg=cyan>Please enter the hostname (multiple allowed: separated by comma ,) [required]: </>');
            $names = trim($helper->ask($input, $output, $question));
        }
        $names = explode(',', $names);
        $names = array_map('trim', $names);


        foreach ($names as $name) {
            if (!$name) {
                continue;
            }
            if (!$this->hostsManager->findHost($name)) {
                $output->writeln(sprintf("<error>The hostname %s does not exists.</error>", $name));
                continue;
            }

            $this->hostsManager->removeHost($name);
            $output->writeln(sprintf("<comment>The hostname %s was removed.</comment>", $name));
        }

        if ($input->getOption('organize')) {
            $this->hostsManager->rebuiltSections();
            $output->writeln('<comment>Hosts file were reorganized automatically.</comment>');
        }

        $this->hostsManager->saveFile();
        $output->writeln('<comment>Hosts file were saved successfully.</comment>');

        $this->outputStructuredHosts($input, $output);
        $output->writeln('');
        $output->writeln('<info>End</info>');
    }
}
