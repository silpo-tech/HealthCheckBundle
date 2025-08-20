<?php

declare(strict_types=1);

namespace HealthCheck\Checker;

interface CheckerInterface
{
    public function isOk(): bool;

    public function getName(): string;
}
