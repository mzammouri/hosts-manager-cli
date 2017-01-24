#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;

$application = new Application();

$application->add(new \MZA\HostsManager\Command\HostsListCommand());
$application->add(new \MZA\HostsManager\Command\HostsAddCommand());
$application->add(new \MZA\HostsManager\Command\HostsRemoveCommand());

$application->run();