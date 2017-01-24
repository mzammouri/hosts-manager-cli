<?php

namespace MZA\HostsManager\Command;

use MZA\HostsManager\Service\HostsManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints as Constraints;
use Symfony\Component\Validator\Validation;

abstract class HostsCommand extends Command
{
    /**
     * @var InputInterface
     */
    protected $input;
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var HostsManager
     */
    protected $hostsManager;

    /**
     * HostsListCommand constructor.
     */
    public function  __construct($name = null)
    {
        $this->hostsManager = new HostsManager();
        parent::__construct($name);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $file = $this->getHostsFile();
        $this->hostsManager->setFile($file)->buildHosts();

        if ($input->getOption('organize')) {
            $this->hostsManager->rebuiltSections();
        }
    }

    protected function configure()
    {
        if (!$this->getDefinition()->hasOption('organize')) {
            $this->addOption('organize', 'o', InputOption::VALUE_NONE, 'Restructure automatically hosts.');
        }
        if (!$this->getDefinition()->hasOption('console')) {
            $this->addOption('console', 'c', InputOption::VALUE_NONE, 'Output hosts structure in console after process.');
        }

        $this->addOption('file', 'f', InputOption::VALUE_OPTIONAL, 'Input hosts file.', $this->hostsManager->getDefaultHostsFile());
    }

    /**
     * @return mixed|string
     */
    protected function getHostsFile()
    {
        $file = $this->input->getOption('file');

        if (!$file) {
            $file = $this->hostsManager->getDefaultHostsFile();
        }

        if (!file_exists($file)) {
            throw new InvalidOptionException("File '$file' does not exists.");
        }

        return $file;
    }

    public function getHostsManager()
    {
        return $this->hostsManager;
    }

    protected function outputStructuredHosts(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('console')) {
            return;
        }
        $this->doOutputStructuredHosts($input, $output);
    }

    protected function doOutputStructuredHosts(InputInterface $input, OutputInterface $output)
    {
        $sections = $this->hostsManager->getSections();

        /** @var Section $section */
        foreach ($sections as $section) {
            $output->writeln("<fg=yellow>{$section->getName()}</>");
            /** @var Host $host */
            foreach ($section->getHosts() as $host) {
                $hostLine = str_pad($host->getIp(), 16, ' ') . $host->getName();
                $output->writeln("<fg=green>\t$hostLine</>");
            }
            //$output->writeln('');
        }
    }


    protected function isValidIp($ip)
    {
        $errors = [];
        $validator = Validation::createValidator();
        $violations = $validator->validate($ip, array(new Constraints\Ip([
            'message' => 'The IP "' . $ip . '" is not a valid IP address.'
        ])));

        if (0 !== count($violations)) {
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
        }

        return $errors;
    }

    protected function isValidHostname($hostname)
    {
        $errors = [];
        $validator = Validation::createValidator();
        $violations = $validator->validate($hostname, array(new Constraints\Length([
            'min' => 3,
            'minMessage' => 'The hostname "' . $hostname . '" is too short. It should have {{ limit }} characters or more.',
        ])));

        if (0 !== count($violations)) {
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
        }

        return $errors;
    }
}
