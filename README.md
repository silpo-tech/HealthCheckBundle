# Health Check Bundle for Symfony Framework #

[![CI](https://github.com/silpo-tech/HealthCheckBundle/actions/workflows/ci.yml/badge.svg)](https://github.com/silpo-tech/HealthCheckBundle/actions)
[![codecov](https://codecov.io/gh/silpo-tech/HealthCheckBundle/graph/badge.svg)](https://codecov.io/gh/silpo-tech/HealthCheckBundle)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)


## About ##

The Health Check Bundle contains endpoint and services that can be used to check if it is up and running

## Installation ##

Require the bundle and its dependencies with composer:

```bash
$ composer require silpo-tech/health-check-bundle
```

Register the bundle:

```php
// project/config/bundles.php

return [
    HealthCheck\HealthCheckBundle::class => ['all' => true],
];
```

Routing:

```yaml
health_check:
  resource: '@HealthCheckBundle/Resources/config/routes.yaml'
```

Configuration:

```yaml
health_check:
  apps:
      # for api
      web:
        checkers:
          - doctrine_mongodb
      # for workers
      command:
        checkers:
          - doctrine_mongodb
```

## Testing ##

### Run Unit and Integration Tests ###

```shell
composer test:run
```

### Run Full Test Suite (including Application Tests) ###

For application tests that require database services, you can use Docker Compose:

```shell
# Start database services
docker-compose -f docker-compose.test.yml up -d

# Run all tests including application tests
composer test:run

# Stop database services
docker-compose -f docker-compose.test.yml down
```

### Run Specific Test Suites ###

```shell
# Unit tests only (no external dependencies)
php bin/phpunit --testsuite unit

# Integration tests only
php bin/phpunit --testsuite integration

# Application tests only (requires database services)
php bin/phpunit --testsuite application
```