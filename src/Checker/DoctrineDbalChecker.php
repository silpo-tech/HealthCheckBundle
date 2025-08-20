<?php

declare(strict_types=1);

namespace HealthCheck\Checker;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;

readonly class DoctrineDbalChecker implements CheckerInterface
{
    public const NAME = 'doctrine_dbal';

    public function __construct(
        private ManagerRegistry $registry,
        private LoggerInterface $logger,
    ) {
    }

    public function isOk(): bool
    {
        try {
            foreach ($this->registry->getConnections() as $connection) {
                $query = $connection->getDriver()->getDatabasePlatform()->getDummySelectSQL();

                // after dbal 2.11 fetchOne replace fetchColumn
                if (method_exists($connection, 'fetchColumn')) {
                    $connection->fetchColumn($query);
                } else {
                    $connection->fetchOne($query);
                }
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
