<?php

namespace MZA\HostsManager\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Validator\Constraints as Constraints;

// Validation

class HostsAddCommand extends HostsCommand
{
    protected function configure()
    {
        $this
            ->setName('hosts:add')
            ->setDescription('Add hostname to local hosts file system.')
            ->addArgument('ip', InputArgument::OPTIONAL, 'Ip address for registered hostname.')
            ->addArgument('name', InputArgument::OPTIONAL, 'Hostname to add')
            ->addArgument('section', InputArgument::OPTIONAL, 'Section where hostname will be stored.')
            ->addOption('console', 'c', InputOption::VALUE_NONE, 'Output hosts structure in console after process.')
            ->addOption('organize', 'o', InputOption::VALUE_NONE, 'Restructure automatically hosts.<fg=red> When used: section argument is obsolete</>');


        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $helper = $this->getHelper('question');

        $ip = $input->getArgument('ip');
        while (!$ip) {
            $question = new Question('<fg=cyan>Please enter the ip address [required]: </>');
            $ip = $helper->ask($input, $output, $question);

            // Validate ip address before process
            if ($errors = $this->isValidIp($ip)) {
                foreach ($errors as $error) {
                    $output->writeln("<error>$error</error>");
                }
                $ip = null;
            }
        }

        $name = $input->getArgument('name');
        while (!$name) {
            $question = new Question('<fg=cyan>Please enter the hostname [required]: </>');
            $name = trim($helper->ask($input, $output, $question));

            // Validate ip address before process
            if ($errors = $this->isValidHostname($name)) {
                foreach ($errors as $error) {
                    $output->writeln("<error>$error</error>");
                }
                $name = null;
            }
        }

        $section = $input->getArgument('section');
        if (!$section) {
            $question = new Question('<fg=cyan>Please enter the section name [optional]: </>');
            $section = $helper->ask($input, $output, $question);
        }

        // Add hosts to file
        $this->hostsManager->addHost($name, $ip, $section ?: null);

        $host = $this->hostsManager->findHost($name);

        $output->writeln('<fg=cyan>Host added : </>');
        $output->writeln("<fg=yellow>{$host->getSection()->getName()}</>");
        $hostIpName = str_pad($host->getIp(), 16, ' ') . $host->getName();
        $output->writeln("<fg=green>\t$hostIpName</>");
        $output->writeln("");

        // Rebuild sections structure if option given
        if ($input->getOption('organize')) {
            $this->hostsManager->rebuiltSections();
            $output->writeln('<comment>Hosts file were reorganized automatically.</comment>');
        }

        // Save file to finish
        $this->hostsManager->saveFile();
        $output->writeln('<comment>Hosts file were saved successfully.</comment>');

        // Show results after adding host if option "--console" given
        $this->outputStructuredHosts($input, $output);
        $output->writeln('');
        $output->writeln('<info>End</info>');
    }

    protected function getQuestionHelper()
    {
        $question = $this->getHelperSet()->get('question');
        if (!$question || get_class($question) !== 'Sensio\Bundle\GeneratorBundle\Command\Helper\QuestionHelper') {
            $this->getHelperSet()->set($question = new QuestionHelper());
        }

        return $question;
    }
}
