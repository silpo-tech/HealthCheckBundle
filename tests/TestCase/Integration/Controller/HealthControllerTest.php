<?php

declare(strict_types=1);

namespace HealthCheck\Tests\TestCase\Integration\Controller;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use HealthCheck\Checker\DoctrineDbalChecker;
use HealthCheck\Checker\DoctrineMongoDBChecker;
use HealthCheck\Controller\HealthController;
use HealthCheck\HealthCheckBundle;
use HealthCheck\Tests\Stub\Kernel;
use HealthCheck\Tests\Traits\MockTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @see HealthController
 */
class HealthControllerTest extends WebTestCase
{
    use MockTrait;

    public static function setUpBeforeClass(): void
    {
        self::$kernel = new Kernel('test', true);
        self::$kernel->boot();
    }

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        /**
         * @var Kernel $kernel
         */
        $kernel = parent::createKernel($options);

        $kernel->addTestBundle(HealthCheckBundle::class);
        $kernel->addTestBundle(DoctrineBundle::class);
        $kernel->addTestBundle(DoctrineMongoDBBundle::class);

        $kernel->addTestConfig(__DIR__.'/../../../Resources/config/packages/health_check.yaml');
        $kernel->addTestConfig(__DIR__.'/../../../Resources/config/packages/doctrine.yaml');
        $kernel->addTestConfig(__DIR__.'/../../../Resources/config/packages/doctrine_mongodb.yaml');

        $kernel->handleOptions($options);

        return $kernel;
    }

    public function testOk(): void
    {
        $client = static::createClient();

        $client->getContainer()->set(
            DoctrineDbalChecker::class,
            new DoctrineDbalChecker($this->createHealthyDbalRegistry(), $this->createLogger())
        );

        $client->getContainer()->set(
            DoctrineMongoDBChecker::class,
            new DoctrineMongoDBChecker($this->createHealthyMongodbRegistry(), $this->createLogger())
        );

        $client->request('GET', '/health/check');

        $response = $client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals([
            'doctrine_dbal' => 'ok',
            'doctrine_mongodb' => 'ok',
            'web_server' => 'ok',
        ], $data);
    }

    public function testFail(): void
    {
        $client = static::createClient();

        $client->getContainer()->set(
            DoctrineDbalChecker::class,
            new DoctrineDbalChecker($this->createHealthyDbalRegistry(), $this->createLogger())
        );

        $client->getContainer()->set(
            DoctrineMongoDBChecker::class,
            new DoctrineMongoDBChecker(
                $this->createUnhealthyMongodbRegistry(),
                $this->createLogger('error', '[health-check] doctrine_mongodb failed, reason MongoDB connection failed')
            )
        );
        $client->request('GET', '/health/check');

        $response = $client->getResponse();

        $this->assertEquals(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);

        $this->assertEquals([
            'doctrine_dbal' => 'ok',
            'doctrine_mongodb' => 'ko',
            'web_server' => 'ok',
        ], $data);
    }
}
