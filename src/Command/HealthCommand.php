<?php

declare(strict_types=1);

namespace HealthCheck\Command;

use HealthCheck\Checker\CheckerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('health:check')]
class HealthCommand extends Command
{
    /** @var CheckerInterface[] */
    private iterable $checkers;

    /** @var string[] */
    private array $commandCheckers;

    public function __construct(iterable $checkers = [], array $commandCheckers = [])
    {
        $this->checkers = $checkers;
        $this->commandCheckers = $commandCheckers;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription(<<<DESCRIPTION
This command applies all health checks from health_check.apps.command.checkers configuration array
DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->checkers as $checker) {
            if (!in_array($checker->getName(), $this->commandCheckers)) {
                continue;
            }

            if (!$checker->isOk()) {
                $output->writeln(sprintf('<error>%s: ko</error>', $checker->getName()));

                return 1;
            }
            $output->writeln(sprintf('<info>%s: ok</info>', $checker->getName()));
        }

        return 0;
    }
}
