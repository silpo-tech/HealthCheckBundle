<?php

declare(strict_types=1);

namespace HealthCheck\Checker;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;

readonly class DoctrineMongoDBChecker implements CheckerInterface
{
    public const NAME = 'doctrine_mongodb';

    public function __construct(
        private ManagerRegistry $registry,
        private LoggerInterface $logger,
    ) {
    }

    public function isOk(): bool
    {
        try {
            foreach ($this->registry->getConnections() as $connection) {
                $connection->listDatabases();
            }
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf('[health-check] %s failed, reason %s', self::NAME, $exception->getMessage()),
            );

            return false;
        }

        return true;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
