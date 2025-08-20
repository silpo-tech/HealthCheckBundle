<?php

declare(strict_types=1);

namespace HealthCheck\Tests\TestCase\Unit\DependencyInjection;

use HealthCheck\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    private Processor $processor;
    private Configuration $configuration;

    protected function setUp(): void
    {
        $this->processor = new Processor();
        $this->configuration = new Configuration();
    }

    public function testEmptyConfig(): void
    {
        $config = $this->processor->processConfiguration($this->configuration, []);

        $this->assertEquals(['apps' => []], $config);
    }

    public function testValidConfig(): void
    {
        $inputConfig = [
            'apps' => [
                'web' => [
                    'checkers' => ['db_checker', 'cache_checker'],
                ],
                'command' => [
                    'checkers' => ['queue_checker'],
                ],
            ],
        ];

        $expectedConfig = [
            'apps' => [
                'web' => [
                    'checkers' => ['db_checker', 'cache_checker'],
                ],
                'command' => [
                    'checkers' => ['queue_checker'],
                ],
            ],
        ];

        $processedConfig = $this->processor->processConfiguration($this->configuration, [$inputConfig]);

        $this->assertEquals($expectedConfig, $processedConfig);
    }

    public function testInvalidConfigCauseMissingCheckers(): void
    {
        $this->expectNotToPerformAssertions();

        $inputConfig = [
            'apps' => [
                'web' => [],
            ],
        ];

        $this->processor->processConfiguration($this->configuration, [$inputConfig]);
    }
}