<?php

if (!$loader = include __DIR__ . '/../vendor/autoload.php') {
    die('You must set up the project dependencies.');
}

$app = new \Cilex\Application('PHProcess Manager', '##development##');

$app->command(new \phmLabs\ProcessManager\Cli\Command\InfoCommand());
$app->command(new \phmLabs\ProcessManager\Cli\Command\RunCommand());
$app->command(new \phmLabs\ProcessManager\Cli\Command\KillCommand());
$app->command(new \phmLabs\ProcessManager\Cli\Command\StartCommand());
$app->command(new \phmLabs\ProcessManager\Cli\Command\StopCommand());
$app->command(new \phmLabs\ProcessManager\Cli\Command\InfoCommand());

$app->run();
