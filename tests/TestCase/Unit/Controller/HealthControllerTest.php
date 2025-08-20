<?php

declare(strict_types=1);

namespace HealthCheck\Tests\TestCase\Unit\Controller;

use HealthCheck\Checker\DoctrineDbalChecker;
use HealthCheck\Checker\DoctrineMongoDBChecker;
use HealthCheck\Controller\HealthController;
use HealthCheck\Tests\Traits\MockTrait;
use PHPUnit\Framework\TestCase;
use SilpoTech\Lib\TestUtilities\TestCase\Traits\UtilityTrait;
use Symfony\Component\HttpFoundation\Response;

class HealthControllerTest extends TestCase
{
    use MockTrait;
    use UtilityTrait;

    /** @dataProvider invokeDataProvider */
    public function testInvoke(array $checkers, callable $checkerServices, array $expected): void
    {
        $checkerServices = $this->resolveValue($checkerServices);
        $controller = new HealthController($checkerServices, $checkers);

        $response = $controller();

        $this->assertEquals($expected['content'], json_decode($response->getContent(), true));
        $this->assertEquals($expected['statusCode'], $response->getStatusCode());
    }

    public static function invokeDataProvider(): iterable
    {
        yield 'ok with mongodb and dbal + web' => [
            'checkers' => [DoctrineDbalChecker::NAME, DoctrineMongoDBChecker::NAME],
            'checkerServices' => function (): array {
                return [
                    new DoctrineDbalChecker(self::$_this->createHealthyDbalRegistry(), self::$_this->createLogger()),
                    new DoctrineMongoDBChecker(self::$_this->createHealthyMongodbRegistry(), self::$_this->createLogger()),
                ];
            },
            'expected' => [
                'content' => [
                    'web_server' => 'ok',
                    'doctrine_dbal' => 'ok',
                    'doctrine_mongodb' => 'ok',
                ],
                'statusCode' => Response::HTTP_OK,
            ],
        ];

        yield 'failed with mongodb' => [
            'checkers' => [DoctrineDbalChecker::NAME, DoctrineMongoDBChecker::NAME],
            'checkerServices' => static function (): array {
                return [
                    new DoctrineDbalChecker(self::$_this->createHealthyDbalRegistry(), self::$_this->createLogger()),
                    new DoctrineMongoDBChecker(
                        self::$_this->createUnhealthyMongodbRegistry(),
                        self::$_this->createLogger('error', '[health-check] doctrine_mongodb failed, reason MongoDB connection failed')
                    ),
                ];
            },
            'expected' => [
                'content' => [
                    'doctrine_dbal' => 'ok',
                    'doctrine_mongodb' => 'ko',
                    'web_server' => 'ok',
                ],
                'statusCode' => Response::HTTP_SERVICE_UNAVAILABLE,
            ],
        ];

        yield 'ok dbal and web' => [
            'checkers' => [DoctrineDbalChecker::NAME],
            'checkerServices' => static function (): array {
                return [
                    new DoctrineDbalChecker(self::$_this->createHealthyDbalRegistry(), self::$_this->createLogger()),
                ];
            },
            'expected' => [
                'content' => [
                    'web_server' => 'ok',
                    'doctrine_dbal' => 'ok',
                ],
                'statusCode' => Response::HTTP_OK,
            ],
        ];

        yield 'ok web only' => [
            'checkers' => [],
            'checkerServices' => static function (): array {
                return [];
            },
            'expected' => [
                'content' => [
                    'web_server' => 'ok',
                ],
                'statusCode' => Response::HTTP_OK,
            ],
        ];

        yield 'with checkers not in checker services' => [
            'checkers' => ['some-checker'],
            'checkerServices' => static function (): array {
                return [
                    new DoctrineDbalChecker(self::$_this->createHealthyDbalRegistry(), self::$_this->createLogger()),
                ];
            },
            'expected' => [
                'content' => [
                    'web_server' => 'ok',
                ],
                'statusCode' => Response::HTTP_OK,
            ],
        ];
    }
}
