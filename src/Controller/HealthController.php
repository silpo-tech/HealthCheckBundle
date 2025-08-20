<?php

declare(strict_types=1);

namespace HealthCheck\Controller;

use HealthCheck\Checker\CheckerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/health/check', name: 'health_check')]
class HealthController
{
    /** @var CheckerInterface[] */
    private iterable $checkers;

    /** @var string[] */
    private array $webCheckers;

    public function __construct(iterable $checkers, array $webCheckers)
    {
        $this->checkers = $checkers;
        $this->webCheckers = $webCheckers;
    }

    public function __invoke(): Response
    {
        $responseData = ['web_server' => 'ok'];
        $responseStatusCode = Response::HTTP_OK;

        foreach ($this->checkers as $checker) {
            if (!in_array($checker->getName(), $this->webCheckers)) {
                continue;
            }

            $responseData[$checker->getName()] = 'ok';
            if (!$checker->isOk()) {
                $responseData[$checker->getName()] = 'ko';
                $responseStatusCode = Response::HTTP_SERVICE_UNAVAILABLE;
            }
        }

        return new JsonResponse($responseData, $responseStatusCode);
    }
}
